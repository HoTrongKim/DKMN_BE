<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $limitInput = $request->input('limit', 10);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 10;
        $limit = max(1, min(50, $limit));

        $tickets = Ticket::query()
            ->with([
                'donHang',
                'trip.tramDi.tinhThanh',
                'trip.tramDen.tinhThanh',
                'trip.nhaVanHanh',
            ])
            ->whereHas('donHang', function ($query) use ($user) {
                $query->where('nguoi_dung_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Ticket $ticket) {
                return $this->transformTicket($ticket);
            })
            ->values();

        $data = $tickets->toArray();

        return response()->json([
            'status' => true,
            'data' => $data,
            'meta' => [
                'count' => count($data),
            ],
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $ticket = Ticket::query()
            ->with([
                'donHang',
                'trip.tramDi.tinhThanh',
                'trip.tramDen.tinhThanh',
                'trip.nhaVanHanh',
            ])
            ->whereHas('donHang', fn ($query) => $query->where('nguoi_dung_id', $user->id))
            ->orderByDesc('created_at')
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $this->transformTicket($ticket),
        ]);
    }

    private function transformTicket(Ticket $ticket): array
    {
        $order = $ticket->donHang;
        $trip = $ticket->trip;

        $from = $order->noi_di
            ?? $trip?->tramDi?->tinhThanh?->ten
            ?? $trip?->tramDi?->ten;

        $to = $order->noi_den
            ?? $trip?->tramDen?->tinhThanh?->ten
            ?? $trip?->tramDen?->ten;

        $pickup = $order->tram_don
            ?? $trip?->tramDi?->ten
            ?? $from;

        $dropoff = $order->tram_tra
            ?? $trip?->tramDen?->ten
            ?? $to;

        $company = $order->ten_nha_van_hanh
            ?? $trip?->nhaVanHanh?->ten;

        $departureLabel = $this->formatDateLabel($trip?->gio_khoi_hanh);

        return [
            'id' => $ticket->id,
            'ticketId' => $ticket->id,
            'orderId' => $order?->id,
            'orderCode' => $order?->ma_don,
            'tripId' => $ticket->trip_id,
            'paymentId' => (string) ($ticket->payment_id ?? $order?->ma_don ?? $ticket->id),
            'gateway' => $order?->cong_thanh_toan ? strtoupper($order->cong_thanh_toan) : null,
            'total' => $this->normalizeAmount($ticket->total_amount_vnd ?: $order?->tong_tien),
            'createdAt' => $this->timestampMs($ticket->created_at) ?? $this->timestampMs($order?->ngay_tao),
            'from' => $from,
            'to' => $to,
            'date' => $departureLabel,
            'passengers' => $order?->so_hanh_khach,
            'pickupStation' => $pickup,
            'dropoffStation' => $dropoff,
            'company' => $company,
            'seats' => $this->parseSeatNumbers($ticket->seat_numbers),
            'status' => $ticket->status,
            'tripStatus' => $this->mapTripStatus($order?->trang_thai_chuyen),
        ];
    }

    private function parseSeatNumbers(?string $seatNumbers): array
    {
        if (!$seatNumbers) {
            return [];
        }

        $parts = array_map('trim', explode(',', $seatNumbers));

        return array_values(array_filter($parts, function ($value) {
            return $value !== '';
        }));
    }

    private function normalizeAmount($value): int
    {
        if ($value === null) {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return (int) round((float) $value);
    }

    private function timestampMs($value): ?int
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->valueOf();
        }

        try {
            return Carbon::parse($value)->valueOf();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function formatDateLabel($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y H:i');
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function mapTripStatus(?string $status): ?string
    {
        return match (strtolower((string) $status)) {
            'da_den', 'hoan_tat', 'ket_thuc', 'completed' => 'completed',
            'dang_di' => 'in_progress',
            'huy' => 'cancelled',
            'cho_khoi_hanh', '' => 'pending',
            default => strtolower((string) $status),
        };
    }
}
