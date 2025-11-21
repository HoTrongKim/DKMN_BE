<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Mã OTP đặt lại mật khẩu</title>
</head>
<body style="margin:0; padding:0; background:#f4f8ff; font-family:'Segoe UI', Arial, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f8ff; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="background:#ffffff; border-radius:12px; box-shadow:0 8px 30px rgba(37,99,235,0.12); overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4da3ff,#1d8cf8); padding:18px 24px; color:#fff;">
                            <h2 style="margin:0; font-size:20px; letter-spacing:0.5px;">DKMN</h2>
                            <p style="margin:4px 0 0; font-size:13px; opacity:0.9;">Mã OTP đặt lại mật khẩu</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 24px 8px;">
                            <p style="margin:0 0 10px; font-size:15px;">Xin chào,</p>
                            <p style="margin:0 0 16px; font-size:15px;">
                                Bạn (hoặc ai đó) vừa yêu cầu đặt lại mật khẩu. Mã OTP của bạn là:
                            </p>
                            <p style="font-size:28px; font-weight:800; letter-spacing:4px; color:#1d4ed8; margin:0 0 16px;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0 0 16px; font-size:15px; color:#0f172a;">
                                Mã có hiệu lực trong <strong>10 phút</strong>. Nếu không phải bạn thực hiện, vui lòng không chia sẻ mã OTP này cho người khác.
                            </p>
                            <p style="margin:0 0 6px; font-size:14px; color:#6b7280;">Cảm ơn bạn đã tin dùng DKMN.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px 24px; background:#f8fafc; border-top:1px solid #eef2ff; color:#6b7280; font-size:13px;">
                            Trân trọng,<br>Đội ngũ hỗ trợ DKMN
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
