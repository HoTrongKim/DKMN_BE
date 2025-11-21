<?php

namespace App\Http\Controllers;

use App\Models\ThongBao;
use Illuminate\Http\Request;

class ThongBaoController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => ThongBao::orderByDesc('ngay_tao')->get()]);
    }

    public function me(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $limitInput = $request->input('limit', 20);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 20;
        $limit = max(1, min(50, $limit));

        $notifications = ThongBao::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('nguoi_dung_id')
                    ->orWhere('nguoi_dung_id', $user->id);
            })
            ->where(function ($query) use ($request) {
                $type = $request->input('type');
                if ($type) {
                    $query->where('loai', $type);
                }
            })
            ->orderByDesc('ngay_tao')
            ->limit($limit)
            ->get()
            ->map(fn (ThongBao $notification) => $this->transform($notification, $user->id))
            ->values();

        return response()->json([
            'status' => true,
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $query = ThongBao::query()->where('nguoi_dung_id', $user->id);
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $updated = $query->update(['da_doc' => 1]);

        return response()->json([
            'status' => true,
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }

    /**
     * Hộp thư đến (loại "inbox") cho người dùng
     */
    public function inbox(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $limitInput = $request->input('limit', 20);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 20;
        $limit = max(1, min(50, $limit));

        $messages = ThongBao::query()
            ->where('loai', ThongBao::LOAI_INBOX)
            ->where(function ($query) use ($user) {
                $query->whereNull('nguoi_dung_id')
                    ->orWhere('nguoi_dung_id', $user->id);
            })
            ->orderByDesc('ngay_tao')
            ->limit($limit)
            ->get()
            ->map(fn (ThongBao $notification) => $this->transform($notification, $user->id))
            ->values();

        return response()->json([
            'status' => true,
            'data' => $messages,
        ]);
    }

    public function markInboxAsRead(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
          return response()->json([
              'message' => 'Unauthenticated',
          ], 401);
        }

        $ids = collect($request->input('ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $query = ThongBao::query()
            ->where('loai', ThongBao::LOAI_INBOX)
            ->where('nguoi_dung_id', $user->id);
        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $updated = $query->update(['da_doc' => 1]);

        return response()->json([
            'status' => true,
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }

    private function transform(ThongBao $notification, ?int $userId = null): array
    {
        return [
            'id' => $notification->id,
            'userId' => $notification->nguoi_dung_id,
            'title' => $notification->tieu_de,
            'message' => $notification->noi_dung,
            'type' => $notification->loai,
            'read' => (bool) $notification->da_doc || ($userId && $notification->nguoi_dung_id === null),
            'createdAt' => $notification->ngay_tao,
        ];
    }
}
