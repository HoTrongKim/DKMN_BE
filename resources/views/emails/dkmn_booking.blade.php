<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>DKMN - Xác nhận đặt vé</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(140deg, #dff6ff, #eefaff);
            margin: 0;
            padding: 40px 16px;
            color: #0f172a;
        }
        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 35px 85px rgba(14, 100, 213, 0.16);
        }
        .hero {
            background: linear-gradient(120deg, #33c2ff, #0fb4f2, #1890ff);
            color: #fff;
            text-align: center;
            padding: 40px 32px;
        }
        .hero h1 {
            margin: 0;
            font-size: 30px;
            letter-spacing: 0.02em;
        }
        .hero p {
            margin: 8px 0 0;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-size: 12px;
        }
        .content {
            padding: 32px;
        }
        .section {
            margin-bottom: 28px;
        }
        .section h3 {
            margin: 0 0 12px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #0f8ecf;
        }
        .card {
            border: 1px solid #d8edff;
            border-radius: 20px;
            padding: 22px;
            background: #f3fbff;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 15px;
        }
        .row span:first-child {
            color: #6b7280;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(15, 180, 242, 0.15);
            color: #0a7ac4;
            font-size: 12px;
            margin-left: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th {
            text-align: left;
            text-transform: uppercase;
            font-size: 11px;
            color: #94a3b8;
            padding-bottom: 8px;
        }
        table td {
            padding: 10px 0;
            border-bottom: 1px solid #e5e9f2;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 15px;
        }
        .totals .total {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 10px;
        }
        .cta {
            margin: 24px 0 0;
            padding: 22px 20px;
            border-radius: 16px;
            background: #dff6ff;
            color: #0a84c1;
            text-align: center;
            border: 1px solid rgba(15, 174, 233, 0.3);
        }
        .cta strong {
            font-size: 18px;
        }
        .footer {
            text-align: center;
            padding: 18px;
            color: #94a3b8;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="hero">
        <h1>DKMN Booking Confirmed</h1>
        <p>ORDER CODE #{{ $data['customer']['bookingCode'] }}</p>
    </div>
    <div class="content">
        <div class="section">
            <h3>Xin chào, {{ $data['customer']['name'] ?? 'Quý khách' }}</h3>
            <p>Cảm ơn bạn đã lựa chọn DKMN. Dưới đây là thông tin vé – hãy lưu lại để xuất trình khi cần.</p>
        </div>

        <div class="section">
            <h3>Chuyến đi</h3>
            <div class="card">
                <div class="row">
                    <span>Tuyến:</span>
                    <span>{{ $data['trip']['route'] }}</span>
                </div>
                <div class="row">
                    <span>Nhà vận hành:</span>
                    <span>{{ $data['trip']['operator'] }} <span class="badge">{{ $data['trip']['vehicle'] }}</span></span>
                </div>
                <div class="row">
                    <span>Khởi hành:</span>
                    <span>{{ $data['trip']['departure'] }}</span>
                </div>
                <div class="row">
                    <span>Dự kiến đến:</span>
                    <span>{{ $data['trip']['arrival'] }}</span>
                </div>
                <div class="row">
                    <span>Điểm đón:</span>
                    <span>{{ $data['trip']['pickup'] }}</span>
                </div>
                <div class="row">
                    <span>Điểm trả:</span>
                    <span>{{ $data['trip']['dropoff'] }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Ghế & giá</h3>
            <div class="card">
                <table>
                    <thead>
                    <tr>
                        <th>Ghế</th>
                        <th>Giá vé</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($data['seats'] as $seat)
                        <tr>
                            <td>{{ $seat['label'] }}</td>
                            <td>{{ number_format($seat['price']) }} đ</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section">
            <h3>Thanh toán</h3>
            <div class="card totals">
                <div>
                    <span>Tạm tính:</span>
                    <span>{{ number_format($data['totals']['subtotal']) }} đ</span>
                </div>
                <div>
                    <span>Giảm giá:</span>
                    <span>{{ number_format($data['totals']['discount']) }} đ</span>
                </div>
                <div class="total">
                    <span>Tổng thanh toán:</span>
                    <span>{{ number_format($data['totals']['total']) }} đ</span>
                </div>
                <div style="margin-top: 12px;">
                    <span>Phương thức:</span>
                    <span>{{ $data['payment']['method'] }} · {{ $data['payment']['status'] }}</span>
                </div>
                <div>
                    <span>Mã đơn:</span>
                    <span>{{ $data['customer']['bookingCode'] }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Khách hàng</h3>
            <div class="card">
                <div class="row">
                    <span>Họ tên:</span>
                    <span>{{ $data['customer']['name'] }}</span>
                </div>
                <div class="row">
                    <span>Số điện thoại:</span>
                    <span>{{ $data['customer']['phone'] }}</span>
                </div>
                <div class="row">
                    <span>Email:</span>
                    <span>{{ $data['customer']['email'] }}</span>
                </div>
            </div>
        </div>

        <div class="cta">
            <strong>Hẹn gặp bạn trên chuyến đi!</strong>
            <p>Vui lòng đến sớm 15 phút và xuất trình email này để nhận vé tại quầy.</p>
        </div>
    </div>
    <div class="footer">
        © {{ date('Y') }} DKMN · Email tự động, vui lòng không trả lời.
    </div>
</div>
</body>
</html>
