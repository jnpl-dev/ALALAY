<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>ALALAY Login Verification</h2>
    <p>Hello {{ $user->first_name }},</p>
    <p>Use the following code to complete your login:</p>
    <h1 style="font-size: 32px; letter-spacing: 8px; text-align: center; padding: 20px; background: #f3f4f6; border-radius: 8px;">{{ $otpCode }}</h1>
    <p>This code will expire in <strong>5 minutes</strong>.</p>
    <p>If you did not attempt to log in, please ignore this email.</p>
    <hr>
    <p style="color: #6b7280; font-size: 12px;">ALALAY — Municipality of General Mamerto Natividad, Nueva Ecija</p>
</body>
</html>
