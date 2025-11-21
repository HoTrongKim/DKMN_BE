<?php

namespace App\Http\Controllers;

use App\Models\DonHang;
use App\Models\ThanhToan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ThanhToanController extends Controller
{
    public function getData()
    {
        return response()->json(['data' => ThanhToan::orderByDesc('ngay_tao')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'donHangId' => 'required|integer|exists:don_hangs,id',
            'congThanhToan' => 'required|string|max:50',
            'soTien' => 'required|numeric|min:0',
            'trangThai' => 'required|string|in:cho,thanh_cong,that_bai,hoan_tien',
            'maGiaoDich' => 'nullable|string|max:100',
            'phanHoi' => 'nullable|string',
            'thoiDiemThanhToan' => 'nullable|date',
        ]);

        $donHang = DonHang::findOrFail($data['donHangId']);
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Yeu cau dang nhap truoc khi cap nhat thanh toan.',
            ], 401);
        }

        $isAdmin = strtolower((string) ($user->vai_tro ?? '')) === 'quan_tri';
        if (!$isAdmin && (int) $donHang->nguoi_dung_id !== (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Khong co quyen cap nhat thanh toan cho don hang nay.',
            ], 403);
        }

        $expectedAmount = round((float) $donHang->tong_tien, 2);
        $providedAmount = round((float) $data['soTien'], 2);

        if (abs($expectedAmount - $providedAmount) > 0.01) {
            return response()->json([
                'status' => false,
                'message' => 'Số tiền thanh toán không khớp với tổng đơn hàng.',
                'expected' => $expectedAmount,
                'provided' => $providedAmount,
            ], 422);
        }

        $payment = ThanhToan::create([
            'don_hang_id' => $donHang->id,
            'ma_thanh_toan' => $this->generatePaymentCode(),
            'cong_thanh_toan' => $data['congThanhToan'],
            'so_tien' => $expectedAmount,
            'trang_thai' => $data['trangThai'],
            'ma_giao_dich' => $data['maGiaoDich'] ?? Str::upper(Str::random(8)),
            'phan_hoi_gateway' => $data['phanHoi'] ?? null,
            'thoi_diem_thanh_toan' => $data['thoiDiemThanhToan'] ?? Carbon::now(),
        ]);

        if ($data['trangThai'] === 'thanh_cong') {
            $donHang->update([
                'trang_thai' => 'da_xac_nhan',
                'ngay_cap_nhat' => Carbon::now(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Lưu trạng thái thanh toán thành công',
            'data' => $payment,
        ], 201);
    }

    private function generatePaymentCode(): string
    {
        return sprintf('PAY%s-%s', Carbon::now()->format('YmdHis'), Str::upper(Str::random(4)));
    }
}
