<?php

use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\Admin\PaymentAdminController;
use App\Http\Controllers\Admin\TripAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\RatingAdminController;
use App\Http\Controllers\Admin\NotificationAdminController;
use App\Http\Controllers\Client\OrderClientController;
use App\Http\Controllers\Client\RatingClientController;
use App\Http\Controllers\CauHinhHeThongController;
use App\Http\Controllers\ChiTietDonHangController as DkmnChiTietDonHangController;
use App\Http\Controllers\ChiTietPhiDonHangController;
use App\Http\Controllers\ChuyenDiController;
use App\Http\Controllers\DanhGiaController;
use App\Http\Controllers\DonHangController;
use App\Http\Controllers\GheController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\NguoiDungQuyenHanController;
use App\Http\Controllers\NhaVanHanhController;
use App\Http\Controllers\NhatKyHoatDongController;
use App\Http\Controllers\PhanHoiController;
use App\Http\Controllers\PhiDichVuController;
use App\Http\Controllers\QuyenHanController as DkmnQuyenHanController;
use App\Http\Controllers\ThanhToanController as DkmnThanhToanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ThongBaoController as DkmnThongBaoController;
use App\Http\Controllers\ThongKeDoanhThuController;
use App\Http\Controllers\TinhThanhController;
use App\Http\Controllers\TramController;
use App\Http\Controllers\HuyVeController;
use App\Http\Controllers\Payment\VnpayController;
use Illuminate\Support\Facades\Route;

// =========================================================
// ======================= DKMN API ========================
// =========================================================

Route::post('/nguoi-dung/dang-nhap', [NguoiDungController::class, 'dangNhap']);
Route::post('/nguoi-dung/dang-ky', [NguoiDungController::class, 'dangKy']);
Route::post('/dkmn/password/forgot', [NguoiDungController::class, 'quenMatKhau']);
Route::post('/dkmn/password/verify-otp', [NguoiDungController::class, 'xacThucOtp']);
Route::post('/dkmn/password/reset', [NguoiDungController::class, 'datLaiMatKhau']);

Route::prefix('dkmn')->group(function () {
    // Lookups for public/client side
    Route::get('/tinh-thanh/get-data', [TinhThanhController::class, 'getData']);
    Route::get('/tram/get-data', [TramController::class, 'getData']);
    Route::get('/nha-van-hanh/get-data', [NhaVanHanhController::class, 'getData']);
    Route::match(['get', 'post'], '/chuyen-di/search', [ChuyenDiController::class, 'search']);
    Route::get('/chuyen-di/{chuyenDi}/ghe', [GheController::class, 'getByChuyenDi']);
    Route::get('/cau-hinh/get-data', [CauHinhHeThongController::class, 'getData']);

    // Webhook callbacks remain open (handled via signature)
    Route::post('/payments/qr/webhook', [PaymentController::class, 'handleQrWebhook']);
    Route::match(['get', 'post'], '/payments/vnpay/ipn', [VnpayController::class, 'ipn']);
    Route::get('/payments/vnpay/return', [VnpayController::class, 'handleReturn']);
});

Route::prefix('dkmn')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [NguoiDungController::class, 'thongTin']);
    Route::put('/me', [NguoiDungController::class, 'capNhatThongTin']);
    Route::post('/don-hang', [DonHangController::class, 'store']);
    Route::post('/thanh-toan', [DkmnThanhToanController::class, 'store']);

    Route::post('/payments/qr/init', [PaymentController::class, 'initQr']);
    Route::post('/payments/vnpay/init', [VnpayController::class, 'init']);
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status']);
    Route::post('/payments/onboard/confirm', [PaymentController::class, 'confirmOnboard']);
    Route::post('/me/change-password', [NguoiDungController::class, 'doiMatKhau']);
    Route::get('/thong-bao', [DkmnThongBaoController::class, 'me']);
    Route::post('/thong-bao/mark-read', [DkmnThongBaoController::class, 'markAsRead']);
    Route::get('/inbox', [DkmnThongBaoController::class, 'inbox']);
    Route::post('/inbox/mark-read', [DkmnThongBaoController::class, 'markInboxAsRead']);
});

Route::prefix('dkmn')->middleware(['auth:sanctum', 'role:quan_tri'])->group(function () {
    Route::get('/chuyen-di/get-data', [ChuyenDiController::class, 'getData']);
    Route::get('/nguoi-dung/get-data', [NguoiDungController::class, 'getData']);
    Route::get('/quyen-han/get-data', [DkmnQuyenHanController::class, 'getData']);
    Route::get('/nguoi-dung-quyen-han/get-data', [NguoiDungQuyenHanController::class, 'getData']);

    Route::get('/don-hang/get-data', [DonHangController::class, 'getData']);
    Route::get('/chi-tiet-don-hang/get-data', [DkmnChiTietDonHangController::class, 'getData']);

    Route::get('/phi-dich-vu/get-data', [PhiDichVuController::class, 'getData']);
    Route::get('/chi-tiet-phi-don-hang/get-data', [ChiTietPhiDonHangController::class, 'getData']);

    Route::get('/thanh-toan/get-data', [DkmnThanhToanController::class, 'getData']);

    Route::get('/huy-ve/get-data', [HuyVeController::class, 'getData']);
    Route::get('/danh-gia/get-data', [DanhGiaController::class, 'getData']);
    Route::get('/phan-hoi/get-data', [PhanHoiController::class, 'getData']);
    Route::get('/thong-bao/get-data', [DkmnThongBaoController::class, 'getData']);
    Route::get('/thong-ke-doanh-thu/get-data', [ThongKeDoanhThuController::class, 'getData']);
    Route::get('/nhat-ky-hoat-dong/get-data', [NhatKyHoatDongController::class, 'getData']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:quan_tri'])->group(function () {
    Route::get('/trips', [TripAdminController::class, 'index']);
    Route::get('/trips/{chuyenDi}', [TripAdminController::class, 'show']);
    Route::post('/trips', [TripAdminController::class, 'store']);
    Route::put('/trips/{chuyenDi}', [TripAdminController::class, 'update']);
    Route::post('/trips/{chuyenDi}/notify', [TripAdminController::class, 'notify']);
    Route::delete('/trips/{chuyenDi}', [TripAdminController::class, 'destroy']);

    Route::get('/orders', [OrderAdminController::class, 'index']);
    Route::get('/orders/{donHang}', [OrderAdminController::class, 'show']);
    Route::patch('/orders/{donHang}', [OrderAdminController::class, 'update']);
    Route::delete('/orders/{donHang}', [OrderAdminController::class, 'destroy']);

    Route::get('/users', [UserAdminController::class, 'index']);
    Route::post('/users', [UserAdminController::class, 'store']);
    Route::patch('/users/{nguoiDung}', [UserAdminController::class, 'update']);
    Route::patch('/users/{nguoiDung}/status', [UserAdminController::class, 'updateStatus']);
    Route::delete('/users/{nguoiDung}', [UserAdminController::class, 'destroy']);

    Route::get('/payments', [PaymentAdminController::class, 'index']);
    Route::get('/payments/export', [PaymentAdminController::class, 'export']);
    Route::get('/statistics/overview', [DashboardAdminController::class, 'overview']);
    Route::get('/statistics/report', [DashboardAdminController::class, 'report']);

    Route::get('/ratings', [RatingAdminController::class, 'index']);
    Route::patch('/ratings/{danhGia}', [RatingAdminController::class, 'update']);
    Route::delete('/ratings/{danhGia}', [RatingAdminController::class, 'destroy']);

    Route::get('/notifications', [NotificationAdminController::class, 'index']);
    Route::post('/notifications', [NotificationAdminController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/latest', [TicketController::class, 'latest']);

    Route::get('/me/orders', [OrderClientController::class, 'index']);
    Route::get('/me/orders/{donHang}', [OrderClientController::class, 'show']);

    Route::get('/ratings/me', [RatingClientController::class, 'index']);
    Route::post('/ratings', [RatingClientController::class, 'store']);
});
