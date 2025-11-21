<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => ['nullable', Rule::in(['cho_duyet', 'chap_nhan', 'tu_choi'])],
            'search' => 'nullable|string|max:150',
        ]);

        $query = DanhGia::query()
            ->with(['nguoiDung', 'chuyenDi.tramDi', 'chuyenDi.tramDen'])
            ->orderByDesc('ngay_tao');

        if (!empty($validated['rating'])) {
            $query->where('diem', $validated['rating']);
        }

        if (!empty($validated['status'])) {
            $query->where('trang_thai', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $keyword = strtolower(trim($validated['search']));
            $query->where(function ($sub) use ($keyword) {
                $sub->whereHas('nguoiDung', fn ($q) => $q->where('ho_ten', 'like', "%{$keyword}%"))
                    ->orWhereHas('chuyenDi.tramDi', fn ($q) => $q->where('ten', 'like', "%{$keyword}%"))
                    ->orWhereHas('chuyenDi.tramDen', fn ($q) => $q->where('ten', 'like', "%{$keyword}%"))
                    ->orWhere('nhan_xet', 'like', "%{$keyword}%");
            });
        }

        $paginator = $query->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()->map(function (DanhGia $rating) {
            $trip = $rating->chuyenDi;
            $from = $trip?->tramDi?->ten;
            $to = $trip?->tramDen?->ten;

            return [
                'id' => $rating->id,
                'rating' => $rating->diem,
                'comment' => $rating->nhan_xet,
                'status' => $rating->trang_thai,
                'customer' => $rating->nguoiDung?->ho_ten ?? 'Khách',
                'trip' => $from && $to ? "{$from} → {$to}" : null,
                'tripId' => $rating->chuyen_di_id,
                'createdAt' => optional($rating->ngay_tao)->toIso8601String(),
            ];
        });

        return $this->respondWithPagination($paginator, $data);
    }

    public function update(Request $request, DanhGia $danhGia): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['cho_duyet', 'chap_nhan', 'tu_choi'])],
        ]);

        $danhGia->update([
            'trang_thai' => $validated['status'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật trạng thái đánh giá.',
        ]);
    }

    public function destroy(DanhGia $danhGia): JsonResponse
    {
        $danhGia->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa đánh giá.',
        ]);
    }
}
