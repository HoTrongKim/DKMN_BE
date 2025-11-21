<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo chuyến đi - DKMN</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #f4f5fb;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #1f2933;
        }
        .wrapper {
            width: 100%;
            padding: 24px 10px;
        }
        .email-card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }
        .email-header {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #ffffff;
            padding: 36px 32px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 0.05em;
        }
        .email-header p {
            margin: 8px 0 0;
            font-size: 15px;
            opacity: 0.95;
        }
        .email-body {
            padding: 32px;
        }
        .section-title {
            font-size: 13px;
            letter-spacing: 0.12em;
            color: #64748b;
            text-transform: uppercase;
            margin: 0 0 12px;
        }
        .notice-box {
            background: #f1f5f9;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
        }
        .notice-box p {
            margin: 0;
            font-size: 15px;
            line-height: 1.6;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 18px;
            background: #fff;
        }
        .stat-label {
            font-size: 12px;
            letter-spacing: 0.08em;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
        }
        .email-footer {
            padding: 20px 32px 32px;
            border-top: 1px solid #edf2f7;
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
        }
        @media (max-width: 480px) {
            .email-body, .email-header, .email-footer {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="email-card">
            <div class="email-header">
                <h1>DKMN</h1>
                <p>Thông báo chuyến đi</p>
            </div>
            <div class="email-body">
                <p class="section-title">Xin chào</p>
                <div class="notice-box">
                    <p>Chào {{ $customerName }},</p>
                    <p style="margin-top: 10px;">{{ $messageBody }}</p>
                </div>

                <p class="section-title">Thông tin chuyến đi</p>
                <div class="grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-label">Tuyến</div>
                        <div class="stat-value">{{ $route }}</div>
                    </div>
                    @if($operator)
                        <div class="stat-card">
                            <div class="stat-label">Nhà vận hành</div>
                            <div class="stat-value">{{ $operator }}</div>
                        </div>
                    @endif
                    @if($vehicle)
                        <div class="stat-card">
                            <div class="stat-label">Phương tiện</div>
                            <div class="stat-value">{{ $vehicle }}</div>
                        </div>
                    @endif
                    @if($departure)
                        <div class="stat-card">
                            <div class="stat-label">Khởi hành</div>
                            <div class="stat-value">{{ $departure }}</div>
                        </div>
                    @endif
                    @if($arrival)
                        <div class="stat-card">
                            <div class="stat-label">Dự kiến đến</div>
                            <div class="stat-value">{{ $arrival }}</div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="email-footer">
                Nếu bạn có thắc mắc, vui lòng trả lời email này hoặc liên hệ đội hỗ trợ DKMN.<br/>
                © {{ date('Y') }} DKMN. Tất cả các quyền được bảo lưu.
            </div>
        </div>
    </div>
</body>
</html>
