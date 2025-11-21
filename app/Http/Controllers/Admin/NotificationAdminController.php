<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Models\ThongBao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $limitInput = $request->input('limit', 20);
        $limit = is_numeric($limitInput) ? (int) $limitInput : 20;
        $limit = max(1, min(100, $limit));

        $notifications = ThongBao::query()
            ->with(['nguoiDung'])
            ->orderByDesc('ngay_tao')
            ->limit($limit)
            ->get()
            ->map(fn (ThongBao $notification) => $this->transform($notification))
            ->values();

        return response()->json([
            'status' => true,
            'data' => $notifications,
            'meta' => [
                'count' => $notifications->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
            'type' => [
                'nullable',
                'string',
                Rule::in(['info', 'warning', 'success', 'error', ThongBao::LOAI_TRIP_UPDATE, ThongBao::LOAI_INBOX]),
            ],
            'recipientIds' => 'nullable|array',
            'recipientIds.*' => 'integer|exists:nguoi_dungs,id',
            'recipientEmails' => 'nullable|array',
            'recipientEmails.*' => 'email',
        ]);

        $type = $validated['type'] ?? 'info';
        $recipientIds = collect($validated['recipientIds'] ?? [])->filter()->unique()->values();
        $recipientEmails = collect($validated['recipientEmails'] ?? [])->filter()->unique()->values();

        $usersQuery = NguoiDung::query()
            ->where('vai_tro', '!=', 'quan_tri');

        if ($recipientIds->isNotEmpty()) {
            $usersQuery->whereIn('id', $recipientIds->all());
        }

        if ($recipientEmails->isNotEmpty()) {
            $usersQuery->whereIn('email', $recipientEmails->all());
        }

        $users = $usersQuery->get()->unique('id');

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy khách hàng phù hợp để gửi thông báo.',
            ], 422);
        }

        $now = Carbon::now();
        $rows = $users->map(function (NguoiDung $user) use ($type, $validated, $now) {
            return [
                'nguoi_dung_id' => $user->id,
                'tieu_de' => $validated['title'],
                'noi_dung' => $validated['message'],
                'loai' => $type,
                'da_doc' => 0,
                'ngay_tao' => $now,
            ];
        })->toArray();

        ThongBao::insert($rows);

        return response()->json([
            'status' => true,
            'message' => 'Đã gửi thông báo đến khách hàng.',
            'data' => [
                'recipients' => count($rows),
                'type' => $type,
            ],
        ], 201);
    }

    private function transform(ThongBao $notification): array
    {
        return [
            'id' => $notification->id,
            'userId' => $notification->nguoi_dung_id,
            'recipient' => $notification->nguoiDung?->ho_va_ten
                ?? $notification->nguoiDung?->ho_ten
                ?? $notification->nguoiDung?->name
                ?? $notification->nguoiDung?->email,
            'title' => $notification->tieu_de,
            'message' => $notification->noi_dung,
            'type' => $notification->loai,
            'read' => (bool) $notification->da_doc,
            'createdAt' => $notification->ngay_tao,
        ];
    }
}
