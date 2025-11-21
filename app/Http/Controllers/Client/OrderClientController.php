<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        $paginator = DonHang::query()
            ->with([
                'chuyenDi.tramDi.tinhThanh',
                'chuyenDi.tramDen.tinhThanh',
                'chuyenDi.nhaVanHanh',
                'ticket',
                'thanhToans',
            ])
            ->where('nguoi_dung_id', $user->id)
            ->orderByDesc('ngay_tao')
            ->paginate($this->resolvePerPage($request, 10));

        $data = $paginator->getCollection()->map(fn (DonHang $order) => $this->transformOrder($order));

        return $this->respondWithPagination($paginator, $data);
    }

    public function show(Request $request, DonHang $donHang): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ((int) $donHang->nguoi_dung_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Không có quyền xem đơn hàng này.',
            ], 403);
        }

        $donHang->load([
            'chuyenDi.tramDi.tinhThanh',
            'chuyenDi.tramDen.tinhThanh',
            'chuyenDi.nhaVanHanh',
            'chiTietDonHang.ghe',
            'ticket',
            'thanhToans',
        ]);

        $items = $donHang->chiTietDonHang->map(function ($item) {
            return [
                'seatId' => $item->ghe_id,
                'seatLabel' => $item->ghe?->so_ghe,
                'passenger' => $item->ten_hanh_khach,
                'phone' => $item->sdt_hanh_khach,
                'price' => (float) $item->gia_ghe,
            ];
        });

        $order = $this->transformOrder($donHang);
        $order['items'] = $items;
        $order['payments'] = $donHang->thanhToans->map(function ($payment) {
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

    private function transformOrder(DonHang $order): array
    {
        $trip = $order->chuyenDi;
        $from = $trip?->tramDi?->ten ?? $order->noi_di;
        $to = $trip?->tramDen?->ten ?? $order->noi_den;

        return [
            'id' => $order->id,
            'code' => $order->ma_don,
            'status' => $this->mapStatusLabel($order->trang_thai),
            'rawStatus' => $order->trang_thai,
            'paymentStatus' => $this->mapPaymentStatus($order),
            'total' => (float) $order->tong_tien,
            'trip' => [
                'id' => $order->chuyen_di_id,
                'from' => $from,
                'to' => $to,
                'departureTime' => $trip?->gio_khoi_hanh
                    ? Carbon::parse($trip->gio_khoi_hanh)->toIso8601String()
                    : null,
                'arrivalTime' => $trip?->gio_den
                    ? Carbon::parse($trip->gio_den)->toIso8601String()
                    : null,
                'operator' => $trip?->nhaVanHanh?->ten,
            ],
            'createdAt' => optional($order->ngay_tao)->toIso8601String(),
        ];
    }

    private function mapStatusLabel(?string $status): string
    {
        return match ($status) {
            'cho_xu_ly' => 'Đang xử lý',
            'da_xac_nhan' => 'Đã xác nhận',
            'hoan_tat' => 'Hoàn tất',
            'da_huy' => 'Đã huỷ',
            default => 'Không xác định',
        };
    }

    private function mapPaymentStatus(DonHang $order): string
    {
        $latest = $order->thanhToans->first();
        $status = $latest?->trang_thai;

        return match ($status) {
            'thanh_cong' => 'Đã thanh toán',
            'hoan_tien' => 'Đã hoàn tiền',
            'that_bai' => 'Thanh toán thất bại',
            default => 'Chưa thanh toán',
        };
    }
}
