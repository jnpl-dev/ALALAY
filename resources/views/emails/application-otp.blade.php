<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>ALALAY Application Tracking</h2>
    <p>Hello,</p>
    <p>Use the following code to verify your identity and track your application ({{ $application->reference_code }}):</p>
    <h1 style="font-size: 32px; letter-spacing: 8px; text-align: center; padding: 20px; background: #f3f4f6; border-radius: 8px;">{{ $otpCode }}</h1>
    <p>This code will expire in <strong>5 minutes</strong>.</p>
    <p>If you did not request this code, please ignore this email.</p>
    <hr>
    <p style="color: #6b7280; font-size: 12px;">ALALAY — Municipality of General Mamerto Natividad, Nueva Ecija</p>
</body>
</html>
