<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\Payment;
use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => ['nullable', Rule::in(['da_dat', 'dang_xu_ly', 'da_di', 'da_huy'])],
            'paymentStatus' => ['nullable', Rule::in(['paid', 'pending', 'refunded'])],
        ]);

        $query = DonHang::query()
            ->with([
                'chuyenDi.tramDi',
                'chuyenDi.tramDen',
                'chuyenDi.nhaVanHanh',
                'nguoiDung',
                'ticket',
                'thanhToans',
                'chiTietDonHang.ghe',
            ])
            ->orderByDesc('ngay_tao');

        if (!empty($validated['search'])) {
            $rawSearch = trim($validated['search']);
            $keyword = Str::lower($rawSearch);
            $idSearch = null;
            if (preg_match('/^\s*#?(\d+)\s*$/', $rawSearch, $matches)) {
                $idSearch = (int) $matches[1];
            }

            $query->where(function ($sub) use ($keyword, $idSearch) {
                $sub->whereRaw('LOWER(ma_don) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(ten_khach) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(sdt_khach) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(noi_di) LIKE ?', ["%{$keyword}%"])
                    ->orWhereRaw('LOWER(noi_den) LIKE ?', ["%{$keyword}%"]);

                if (!is_null($idSearch)) {
                    $sub->orWhere('id', $idSearch);
                }
            });
        }

        if (!empty($validated['status'])) {
            $query->where('trang_thai', $this->mapFilterStatus($validated['status']));
        }

        if (!empty($validated['paymentStatus'])) {
            $status = $validated['paymentStatus'];
            if ($status === 'paid') {
                $query->whereHas('thanhToans', fn ($q) => $q->where('trang_thai', 'thanh_cong'));
            } elseif ($status === 'refunded') {
                $query->whereHas('thanhToans', fn ($q) => $q->where('trang_thai', 'hoan_tien'));
            } else {
                $query->whereDoesntHave('thanhToans', fn ($q) => $q->where('trang_thai', 'thanh_cong'));
            }
        }

        $paginator = $query->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()
            ->map(fn (DonHang $order) => $this->transformOrder($order));

        return $this->respondWithPagination($paginator, $data);
    }

    public function show(DonHang $donHang)
    {
        $donHang->load([
            'chuyenDi.tramDi.tinhThanh',
            'chuyenDi.tramDen.tinhThanh',
            'chuyenDi.nhaVanHanh',
            'chiTietDonHang.ghe',
            'thanhToans',
            'ticket',
        ]);

        $order = $this->transformOrder($donHang);
        $order['items'] = $donHang->chiTietDonHang->map(function ($item) {
            return [
                'seatId' => $item->ghe_id,
                'seatLabel' => $item->ghe?->so_ghe,
                'passenger' => $item->ten_hanh_khach,
                'phone' => $item->sdt_hanh_khach,
                'price' => (float) $item->gia_ghe,
            ];
        });
        $order['payments'] = $donHang->thanhToans->map(function (ThanhToan $payment) {
            return [
                'id' => $payment->id,
                'gateway' => $payment->cong_thanh_toan,
                'amount' => (float) $payment->so_tien,
                'status' => $payment->trang_thai,
                'paidAt' => optional($payment->thoi_diem_thanh_toan)->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $order,
        ]);
    }

    public function update(Request $request, DonHang $donHang)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['cho_xu_ly', 'da_xac_nhan', 'hoan_tat', 'da_huy'])],
            'paymentStatus' => ['nullable', Rule::in(['paid', 'pending', 'refunded'])],
            'paymentGateway' => 'nullable|string|max:50',
        ]);

        $changes = [];
        if (!empty($validated['status'])) {
            $changes['trang_thai'] = $validated['status'];
        }

        if (!empty($changes)) {
            $changes['ngay_cap_nhat'] = now();
            $donHang->fill($changes)->save();
        }

        if (!empty($validated['paymentStatus'])) {
            $this->syncPaymentStatus(
                $donHang,
                $validated['paymentStatus'],
                $validated['paymentGateway'] ?? 'admin_manual'
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật đơn hàng thành công.',
            'data' => $donHang->fresh([
                'chuyenDi.tramDi',
                'chuyenDi.tramDen',
                'thanhToans',
            ]),
        ]);
    }

    public function destroy(DonHang $donHang)
    {
        DB::transaction(function () use ($donHang) {
            $donHang->chiTietDonHang()->delete();
            $donHang->thanhToans()->delete();
            if ($ticket = $donHang->ticket()->first()) {
                Payment::where('ticket_id', $ticket->id)->delete();
                $ticket->delete();
            }
            $donHang->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa đơn hàng.',
        ]);
    }

    private function transformOrder(DonHang $order): array
    {
        $trip = $order->chuyenDi;
        $from = $trip?->tramDi?->ten ?? $order->noi_di;
        $to = $trip?->tramDen?->ten ?? $order->noi_den;
        $departureRaw = $trip?->gio_khoi_hanh;
        $paymentStatusCode = $this->resolvePaymentStatusCode($order);
        $statusLabel = $this->mapStatusLabel($order->trang_thai);
        $user = $order->nguoiDung;

        $accountName = $user?->ho_ten;
        $accountEmail = $user?->email;
        $accountPhone = $user?->so_dien_thoai;

        $customerName = $order->ten_khach ?: ($accountName ?: 'Khách lẻ');
        $customerPhone = $order->sdt_khach ?: $accountPhone;
        $customerEmail = $order->email_khach ?: $accountEmail;

        return [
            'id' => $order->id,
            'code' => $order->ma_don,
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
            'customerEmail' => $customerEmail,
            'accountName' => $accountName,
            'accountEmail' => $accountEmail,
            'accountPhone' => $accountPhone,
            'tripDetail' => trim(($from ?? '') . ' → ' . ($to ?? '')),
            'departureTime' => $this->formatDisplayDate($departureRaw),
            'departureTimeRaw' => $departureRaw ? Carbon::parse($departureRaw)->toIso8601String() : null,
            'orderDate' => $this->formatDisplayDate($order->ngay_tao),
            'orderDateRaw' => optional($order->ngay_tao)->toIso8601String(),
            'total' => (float) $order->tong_tien,
            'totalAmount' => (float) $order->tong_tien,
            'status' => $statusLabel,
            'rawStatus' => $order->trang_thai,
            'paymentStatus' => $this->mapPaymentLabel($paymentStatusCode),
            'paymentStatusCode' => $paymentStatusCode,
            'createdAt' => optional($order->ngay_tao)->toIso8601String(),
            'pickupStation' => $order->tram_don ?? $trip?->tramDi?->ten,
            'dropoffStation' => $order->tram_tra ?? $trip?->tramDen?->ten,
            'operator' => $trip?->nhaVanHanh?->ten,
        ];
    }

    private function mapFilterStatus(string $status): string
    {
        return match ($status) {
            'da_dat' => 'da_xac_nhan',
            'dang_xu_ly' => 'cho_xu_ly',
            'da_di' => 'hoan_tat',
            'da_huy' => 'da_huy',
            default => 'cho_xu_ly',
        };
    }

    private function mapStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xu_ly' => 'Đang xử lý',
            'da_xac_nhan' => 'Đã đặt',
            'hoan_tat' => 'Đã đi',
            'da_huy' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    private function resolvePaymentStatusCode(DonHang $order): string
    {
        $latest = $order->thanhToans->first();
        return match ($latest?->trang_thai) {
            'thanh_cong' => 'paid',
            'hoan_tien' => 'refunded',
            default => 'pending',
        };
    }

    private function mapPaymentLabel(string $statusCode): string
    {
        return match ($statusCode) {
            'paid' => 'Đã TT',
            'refunded' => 'Hoàn tiền',
            default => 'Chờ TT',
        };
    }

    private function syncPaymentStatus(DonHang $order, string $status, string $gateway): void
    {
        $statusMap = [
            'paid' => 'thanh_cong',
            'pending' => 'cho',
            'refunded' => 'hoan_tien',
        ];

        $internalStatus = $statusMap[$status] ?? 'cho';
        $allowedGateways = ['momo', 'zalopay', 'paypal', 'ngan_hang', 'tra_sau'];
        $gatewayValue = in_array($gateway, $allowedGateways, true) ? $gateway : 'ngan_hang';

        ThanhToan::create([
            'don_hang_id' => $order->id,
            'ma_thanh_toan' => sprintf('ADM-%s', strtoupper(Str::random(6))),
            'cong_thanh_toan' => $gatewayValue,
            'so_tien' => $order->tong_tien,
            'trang_thai' => $internalStatus,
            'ma_giao_dich' => strtoupper(Str::random(10)),
            'thoi_diem_thanh_toan' => now(),
        ]);
    }

    private function formatDisplayDate($value, string $format = 'd/m/Y H:i'): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
