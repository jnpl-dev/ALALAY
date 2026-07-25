<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h2>New Contact Message</h2>
    <p><strong>Name:</strong> {{ $senderName }}</p>
    <p><strong>Email:</strong> {{ $senderEmail }}</p>
    <hr>
    <p><strong>Message:</strong></p>
    <p style="white-space: pre-wrap;">{{ $senderMessage }}</p>
    <hr>
    <p style="color: #6b7280; font-size: 12px;">Sent via ALALAY Contact Form</p>
</body>
</html>
