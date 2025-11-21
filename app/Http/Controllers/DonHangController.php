<?php

namespace App\Http\Controllers;

use App\Models\ChiTietDonHang;
use App\Models\ChuyenDi;
use App\Models\DonHang;
use App\Models\Ghe;
use App\Models\NhatKyHoatDong;
use App\Models\Ticket;
use App\Models\ThongBao;
use App\Services\TripSeatSynchronizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DonHangController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => DonHang::orderByDesc('ngay_tao')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tripId' => 'required|integer|exists:chuyen_dis,id',
            'seatIds' => 'nullable|array',
            'seatIds.*' => 'required|string',
            'seatLabels' => 'nullable|array',
            'seatLabels.*' => 'required|string',
            'total' => 'required|numeric|min:0',
            'passengers' => 'required|integer|min:1',
            'from' => 'nullable|string|max:200',
            'to' => 'nullable|string|max:200',
            'pickupStation' => 'nullable|string|max:200',
            'dropoffStation' => 'nullable|string|max:200',
            'company' => 'nullable|string|max:200',
            'gateway' => 'nullable|string|max:50',
            'customerName' => 'nullable|string|max:100',
            'customerPhone' => 'nullable|string|max:20',
            'customerEmail' => 'nullable|string|email|max:100',
        ]);

        $identifiers = $this->normalizeSeatIdentifiers(
            $data['seatIds'] ?? [],
            $data['seatLabels'] ?? []
        );

        if (empty($identifiers)) {
            return response()->json([
                'status' => false,
                'message' => 'Chưa chọn ghế nào.',
            ], 422);
        }

        $authenticatedUser = $request->user('sanctum') ?? $request->user();
        $userId = $authenticatedUser?->id;

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Yeu cau dang nhap truoc khi dat ve.',
            ], 401);
        }

        $booking = DB::transaction(function () use ($data, $identifiers, $request, $userId) {
            $trip = ChuyenDi::with(['tramDi.tinhThanh', 'tramDen.tinhThanh', 'nhaVanHanh'])
                ->lockForUpdate()
                ->findOrFail($data['tripId']);

            TripSeatSynchronizer::sync($trip);
            $context = $this->buildTripContext($trip, $data, count($identifiers));

            $seats = $this->fetchSeatsForTrip($trip->id, $identifiers);
            if ($seats->count() !== count($identifiers)) {
                TripSeatSynchronizer::sync($trip);
                $seats = $this->fetchSeatsForTrip($trip->id, $identifiers);
            }
            if ($seats->count() !== count($identifiers)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Một số ghế không tồn tại hoặc không thuộc chuyến đã chọn.',
                ], 422);
            }

            $blocked = $seats->filter(fn ($seat) => $seat->trang_thai !== 'trong');
            if ($blocked->isNotEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Một hoặc nhiều ghế đã bị đặt trước đó.',
                ], 422);
            }

            $orderPayload = $this->orderPayload($request, $userId, $trip->id, $context, $data);
            $order = DonHang::create($orderPayload);

            $now = Carbon::now();
            $detailRows = $seats->map(function (Ghe $seat) use ($order, $data, $now) {
                return [
                    'don_hang_id' => $order->id,
                    'ghe_id' => $seat->id,
                    'ten_hanh_khach' => $data['customerName'] ?: 'Hành khách',
                    'sdt_hanh_khach' => $data['customerPhone'] ?: null,
                    'gia_ghe' => $seat->gia,
                    'ngay_tao' => $now,
                ];
            })->toArray();

            if (!empty($detailRows)) {
                ChiTietDonHang::insert($detailRows);
            }

            $seatUpdate = ['trang_thai' => 'da_dat'];
            if (Schema::hasColumn('ghes', 'ngay_cap_nhat')) {
                $seatUpdate['ngay_cap_nhat'] = $now;
            }
            Ghe::whereIn('id', $seats->pluck('id')->toArray())->update($seatUpdate);

            $trip->update([
                'ghe_con' => max(0, $trip->ghe_con - $seats->count()),
                'ngay_cap_nhat' => $now,
            ]);

            $seatNumbers = $seats->pluck('so_ghe')->implode(',');
            $seatCount = $seats->count();
            $baseFare = (int) round($seats->sum('gia'));

            if ($baseFare <= 0) {
                $baseFare = (int) round(($trip->gia_co_ban ?? 0) * max(1, $seatCount));
            }

            if ($baseFare <= 0) {
                $baseFare = (int) round($data['total'] ?? 0);
            }

            $ticket = Ticket::create([
                'don_hang_id' => $order->id,
                'trip_id' => $trip->id,
                'seat_numbers' => $seatNumbers,
                'base_fare_vnd' => $baseFare,
                'total_amount_vnd' => $baseFare,
            ]);

            $order->forceFill([
                'tong_tien' => $baseFare,
            ])->save();

            return [
                'donHang' => $order,
                'seats' => $seats,
                'ticket' => $ticket,
                'trip' => $trip,
                'context' => $context,
            ];
        });

        if ($booking instanceof \Illuminate\Http\JsonResponse) {
            return $booking;
        }

        $trip = $booking['trip'];
        $context = $booking['context'];
        $seats = $booking['seats'];
        unset($booking['trip'], $booking['context']);

        $this->logBookingAttempt(
            $booking['donHang'],
            $trip,
            $context,
            $seats instanceof \Illuminate\Support\Collection ? $seats->count() : count($seats),
            $request
        );

        $booking['context'] = $context;

        return response()->json([
            'status' => true,
            'message' => 'Đã lưu đơn hàng tạm thời.',
            'data' => $booking,
        ], 201);
    }

    private function normalizeSeatIdentifiers(array $seatIds, array $seatLabels): array
    {
        $normalizedIds = [];
        foreach ($seatIds as $seatId) {
            $value = trim((string) $seatId);
            if ($value === '') {
                continue;
            }
            $normalizedIds[] = $value;
        }

        if (!empty($normalizedIds)) {
            return array_values(array_unique($normalizedIds));
        }

        $normalizedLabels = [];
        foreach ($seatLabels as $label) {
            $value = trim((string) $label);
            if ($value === '') {
                continue;
            }
            $normalizedLabels[] = $value;
        }

        return array_values(array_unique($normalizedLabels));
    }

    private function fetchSeatsForTrip(int $tripId, array $identifiers)
    {
        $numericIds = array_values(array_filter($identifiers, fn ($value) => is_numeric($value)));
        $labels = array_values(array_filter($identifiers, fn ($value) => !is_numeric($value)));

        $query = Ghe::where('chuyen_di_id', $tripId);
        $query->where(function ($sub) use ($numericIds, $labels) {
            if (!empty($numericIds)) {
                $sub->whereIn('id', array_map('intval', $numericIds));
            }
            if (!empty($labels)) {
                if (!empty($numericIds)) {
                    $sub->orWhereIn('so_ghe', $labels);
                } else {
                    $sub->whereIn('so_ghe', $labels);
                }
            }
        });

        return $query->lockForUpdate()->get();
    }

    private function generateOrderCode(): string
    {
        return sprintf('ORD%s-%s', Carbon::now()->format('YmdHis'), Str::upper(Str::random(4)));
    }

    private function orderPayload(Request $request, ?int $userId, int $tripId, array $context, array $data): array
    {
        $payload = [
            'nguoi_dung_id' => $userId,
            'chuyen_di_id' => $tripId,
            'ma_don' => $this->generateOrderCode(),
            'ten_khach' => $this->resolveCustomerName($data, $request),
            'sdt_khach' => $this->resolveCustomerPhone($data, $request),
            'email_khach' => $this->resolveCustomerEmail($data, $request),
            'tong_tien' => $data['total'],
            'trang_thai' => 'cho_xu_ly',
            'trang_thai_chuyen' => 'cho_khoi_hanh',
        ];

        $optional = [
            'noi_di' => $context['fromCity'],
            'noi_den' => $context['toCity'],
            'tram_don' => $context['pickupStation'],
            'tram_tra' => $context['dropoffStation'],
            'so_hanh_khach' => $context['passengers'],
            'ten_nha_van_hanh' => $context['company'],
            'cong_thanh_toan' => $context['gateway'],
        ];

        foreach ($optional as $column => $value) {
            if (Schema::hasColumn('don_hangs', $column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    private function resolveCustomerName(array $data, Request $request): string
    {
        $fromPayload = trim((string) ($data['customerName'] ?? ''));
        if ($fromPayload !== '') {
            return $fromPayload;
        }

        $user = $request->user('sanctum') ?? $request->user();

        return $user?->ho_ten ?: $user?->name ?: 'Quý khách';
    }

    private function resolveCustomerPhone(array $data, Request $request): ?string
    {
        $fromPayload = trim((string) ($data['customerPhone'] ?? ''));
        if ($fromPayload !== '') {
            return $fromPayload;
        }

        $user = $request->user('sanctum') ?? $request->user();

        return $user?->so_dien_thoai ?: $user?->phone;
    }

    private function resolveCustomerEmail(array $data, Request $request): ?string
    {
        $fromPayload = trim((string) ($data['customerEmail'] ?? ''));
        if ($fromPayload !== '') {
            return $fromPayload;
        }

        $user = $request->user('sanctum') ?? $request->user();

        return $user?->email ?: null;
    }

    private function buildTripContext(ChuyenDi $trip, array $payload, int $seatCount): array
    {
        $fromCity = $this->firstNonEmpty(
            $payload['from'] ?? null,
            $trip->tramDi?->tinhThanh?->ten,
            $trip->tramDi?->ten
        );
        $toCity = $this->firstNonEmpty(
            $payload['to'] ?? null,
            $trip->tramDen?->tinhThanh?->ten,
            $trip->tramDen?->ten
        );

        $pickupStation = $this->firstNonEmpty(
            $payload['pickupStation'] ?? null,
            $trip->tramDi?->ten,
            $fromCity
        );
        $dropoffStation = $this->firstNonEmpty(
            $payload['dropoffStation'] ?? null,
            $trip->tramDen?->ten,
            $toCity
        );

        $company = $trip->nhaVanHanh?->ten ?: ($payload['company'] ?? null);
        $passengers = max(1, (int) ($payload['passengers'] ?? $seatCount));
        $gateway = trim((string) ($payload['gateway'] ?? '')) ?: null;

        return [
            'fromCity' => $fromCity,
            'toCity' => $toCity,
            'pickupStation' => $pickupStation,
            'dropoffStation' => $dropoffStation,
            'company' => $company,
            'passengers' => $passengers,
            'gateway' => $gateway,
        ];
    }

    private function firstNonEmpty(...$values): ?string
    {
        foreach ($values as $value) {
            $trimmed = is_string($value) ? trim($value) : $value;
            if ($trimmed !== null && $trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }

    private function logBookingAttempt(DonHang $order, ChuyenDi $trip, array $context, int $seatCount, Request $request): void
    {
        try {
            $actorId = $order->nguoi_dung_id
                ?? optional($request->user('sanctum'))->id
                ?? optional($request->user())->id;

            NhatKyHoatDong::create([
                'nguoi_dung_id' => $actorId,
                'hanh_dong' => 'dat_ve_tam',
                'mo_ta' => sprintf(
                    'Đặt %d ghế %s → %s (Đơn %s, chuyến #%d)',
                    $seatCount,
                    $context['fromCity'] ?? ($trip->tramDi?->tinhThanh?->ten ?: $trip->tramDi?->ten),
                    $context['toCity'] ?? ($trip->tramDen?->tinhThanh?->ten ?: $trip->tramDen?->ten),
                    $order->ma_don,
                    $trip->id
                ),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function createOrderNotification(DonHang $order, ChuyenDi $trip, $seats, array $context): void
    {
        if (!$order->nguoi_dung_id) {
            return;
        }

        try {
            $from = $context['fromCity']
                ?? $trip->tramDi?->tinhThanh?->ten
                ?? $trip->tramDi?->ten
                ?? 'Điểm đi';
            $to = $context['toCity']
                ?? $trip->tramDen?->tinhThanh?->ten
                ?? $trip->tramDen?->ten
                ?? 'Điểm đến';

            $departure = optional($trip->gio_khoi_hanh)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
            $seatLabels = collect(
                $seats instanceof \Illuminate\Support\Collection ? $seats : (is_array($seats) ? $seats : [])
            )
                ->map(fn ($seat) => $seat instanceof Ghe ? $seat->so_ghe : ($seat['so_ghe'] ?? null))
                ->filter()
                ->implode(', ');

            $title = 'Đặt vé thành công';
            $messageParts = [
                sprintf('Bạn đã đặt vé mã %s cho chuyến %s → %s.', $order->ma_don, $from, $to),
            ];

            if ($departure) {
                $messageParts[] = 'Giờ khởi hành: ' . $departure;
            }

            if ($seatLabels) {
                $messageParts[] = 'Ghế: ' . $seatLabels;
            }

            $amount = (int) round((float) $order->tong_tien);
            if ($amount > 0) {
                $messageParts[] = 'Tổng tiền: ' . number_format($amount, 0, ',', '.') . ' đ';
            }

            ThongBao::create([
                'nguoi_dung_id' => $order->nguoi_dung_id,
                'tieu_de' => $title,
                'noi_dung' => implode(' ', $messageParts),
                'loai' => 'success',
                'da_doc' => 0,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
