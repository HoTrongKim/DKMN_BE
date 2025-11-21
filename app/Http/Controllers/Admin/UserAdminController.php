<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use App\Models\DonHang;
use App\Models\HuyVe;
use App\Models\NguoiDung;
use App\Models\NhatKyHoatDong;
use App\Models\PhanHoi;
use App\Models\ThongBao;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:150',
            'status' => 'nullable|string|in:active,locked',
            'role' => 'nullable|string|in:customer,admin',
        ]);

        $query = NguoiDung::query()->orderByDesc('ngay_tao');

        if (!empty($validated['keyword'])) {
            $keyword = Str::lower(trim($validated['keyword']));
            $query->where(function ($sub) use ($keyword) {
                $sub->where('ho_ten', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$keyword}%");
            });
        }

        if (!empty($validated['status'])) {
            $query->where('trang_thai', $validated['status'] === 'active' ? 'hoat_dong' : 'khoa');
        }

        if (!empty($validated['role'])) {
            $query->where('vai_tro', $validated['role'] === 'admin' ? 'quan_tri' : 'khach_hang');
        }

        $paginator = $query->paginate($this->resolvePerPage($request));
        $data = $paginator->getCollection()->map(fn (NguoiDung $user) => $this->transformUser($user));

        return $this->respondWithPagination($paginator, $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:nguoi_dungs,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['customer', 'admin'])],
            'status' => ['required', Rule::in(['active', 'locked'])],
        ]);

        $user = NguoiDung::create([
            'ho_ten' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'so_dien_thoai' => $validated['phone'] ?? null,
            'mat_khau' => Hash::make($validated['password']),
            'vai_tro' => $validated['role'] === 'admin' ? 'quan_tri' : 'khach_hang',
            'trang_thai' => $validated['status'] === 'active' ? 'hoat_dong' : 'khoa',
        ]);

        return response()->json([
            'status' => true,
            'data' => $this->transformUser($user),
        ], 201);
    }

    public function update(Request $request, NguoiDung $nguoiDung)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'string',
                'email',
                'max:100',
                Rule::unique('nguoi_dungs', 'email')->ignore($nguoiDung->id),
            ],
            'phone' => 'nullable|string|max:20',
            'role' => 'nullable|string|in:customer,admin',
            'status' => 'nullable|string|in:active,locked',
            'password' => 'nullable|string|min:6',
        ]);

        $payload = [];

        if (!empty($validated['name'])) {
            $payload['ho_ten'] = $validated['name'];
        }

        if (!empty($validated['email'])) {
            $payload['email'] = Str::lower($validated['email']);
        }

        if (!empty($validated['phone'])) {
            $payload['so_dien_thoai'] = $validated['phone'];
        }

        if (!empty($validated['role'])) {
            $payload['vai_tro'] = $validated['role'] === 'admin' ? 'quan_tri' : 'khach_hang';
        }

        if (!empty($validated['status'])) {
            $payload['trang_thai'] = $validated['status'] === 'active' ? 'hoat_dong' : 'khoa';
        }

        if (!empty($validated['password'])) {
            $payload['mat_khau'] = Hash::make($validated['password']);
        }

        if (!empty($payload)) {
            $payload['ngay_cap_nhat'] = now();
            $nguoiDung->fill($payload)->save();
        }

        return response()->json([
            'status' => true,
            'data' => $this->transformUser($nguoiDung->fresh()),
        ]);
    }

    public function updateStatus(Request $request, NguoiDung $nguoiDung)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'locked'])],
        ]);

        $nguoiDung->fill([
            'trang_thai' => $validated['status'] === 'active' ? 'hoat_dong' : 'khoa',
            'ngay_cap_nhat' => now(),
        ])->save();

        return response()->json([
            'status' => true,
            'data' => $this->transformUser($nguoiDung->fresh()),
        ]);
    }

    public function destroy(Request $request, NguoiDung $nguoiDung)
    {
        $authId = $request->user()?->id;
        if ($authId && $nguoiDung->id === (int) $authId) {
            return response()->json([
                'status' => false,
                'message' => 'Khong the xoa tai khoan dang dang nhap.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($nguoiDung) {
                $userId = $nguoiDung->id;

                DonHang::where('nguoi_dung_id', $userId)->update(['nguoi_dung_id' => null]);
                ThongBao::where('nguoi_dung_id', $userId)->update(['nguoi_dung_id' => null]);
                NhatKyHoatDong::where('nguoi_dung_id', $userId)->update(['nguoi_dung_id' => null]);
                PhanHoi::where('nguoi_dung_id', $userId)->update(['nguoi_dung_id' => null]);
                PhanHoi::where('nguoi_phu_trach', $userId)->update(['nguoi_phu_trach' => null]);
                HuyVe::where('nguoi_xu_ly', $userId)->update(['nguoi_xu_ly' => null]);
                DanhGia::where('nguoi_dung_id', $userId)->delete();

                $nguoiDung->delete();
            });
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json([
                'status' => false,
                'message' => 'Khong the xoa nguoi dung do dang duoc su dung o khu vuc khac.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Da xoa nguoi dung.',
        ]);
    }

    private function transformUser(NguoiDung $user): array
    {
        $roleCode = $this->normalizeRole($user->vai_tro);
        $statusCode = $this->normalizeStatus($user->trang_thai);

        return [
            'id' => $user->id,
            'name' => $user->ho_ten,
            'email' => $user->email,
            'phone' => $user->so_dien_thoai,
            'role' => $this->mapRoleLabel($roleCode),
            'roleCode' => $roleCode,
            'status' => $this->mapStatusLabel($statusCode),
            'statusCode' => $statusCode,
            'createdAt' => optional($user->ngay_tao)->toIso8601String(),
            'createdAtText' => $this->formatDisplayDate($user->ngay_tao),
        ];
    }

    private function normalizeRole(?string $role): string
    {
        return $role === 'quan_tri' ? 'admin' : 'customer';
    }

    private function normalizeStatus(?string $status): string
    {
        return $status === 'khoa' ? 'locked' : 'active';
    }

    private function mapRoleLabel(string $roleCode): string
    {
        return match ($roleCode) {
            'admin' => 'Quan tri',
            default => 'Khach hang',
        };
    }

    private function mapStatusLabel(string $statusCode): string
    {
        return match ($statusCode) {
            'locked' => 'Da khoa',
            default => 'Hoat dong',
        };
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
