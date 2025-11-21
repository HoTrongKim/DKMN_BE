<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        $ratings = DanhGia::query()
            ->where('nguoi_dung_id', $user->id)
            ->when(
                $request->integer('tripId'),
                fn ($query, $tripId) => $query->where('chuyen_di_id', $tripId)
            )
            ->orderByDesc('ngay_tao')
            ->get()
            ->map(function (DanhGia $rating) {
                return [
                    'id' => $rating->id,
                    'tripId' => $rating->chuyen_di_id,
                    'orderId' => $rating->don_hang_id,
                    'rating' => $rating->diem,
                    'comment' => $rating->nhan_xet,
                    'status' => $rating->trang_thai,
                    'createdAt' => optional($rating->ngay_tao)->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $ratings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        $validated = $request->validate([
            'tripId' => 'required|integer|exists:chuyen_dis,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $order = DonHang::query()
            ->where('nguoi_dung_id', $user->id)
            ->where('chuyen_di_id', $validated['tripId'])
            ->orderByDesc('ngay_tao')
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy đơn hàng hợp lệ cho chuyến đi này.',
            ], 422);
        }

        if (!in_array($order->trang_thai, ['hoan_tat', 'da_xac_nhan'])) {
            return response()->json([
                'status' => false,
                'message' => 'Đơn hàng chưa hoàn tất, không thể đánh giá.',
            ], 422);
        }

        $existing = DanhGia::query()
            ->where('nguoi_dung_id', $user->id)
            ->where('chuyen_di_id', $validated['tripId'])
            ->where('don_hang_id', $order->id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn đã đánh giá chuyến đi này.',
            ], 409);
        }

        $rating = DanhGia::create([
            'nguoi_dung_id' => $user->id,
            'chuyen_di_id' => $validated['tripId'],
            'don_hang_id' => $order->id,
            'diem' => $validated['rating'],
            'nhan_xet' => $validated['comment'] ?: null,
            'trang_thai' => 'cho_duyet',
            'ngay_tao' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đã ghi nhận đánh giá. Hệ thống sẽ duyệt trong thời gian sớm nhất.',
            'data' => [
                'id' => $rating->id,
                'tripId' => $rating->chuyen_di_id,
                'orderId' => $rating->don_hang_id,
                'rating' => $rating->diem,
                'comment' => $rating->nhan_xet,
                'status' => $rating->trang_thai,
                'createdAt' => optional($rating->ngay_tao)->toIso8601String(),
            ],
        ], 201);
    }
}
