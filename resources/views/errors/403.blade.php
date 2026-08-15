<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ALALAY — Access Denied</title>
    <style>
        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ecfdf5, #ffffff, #ecfdf5);
            color: #064e3b;
        }
        .wrapper {
            width: 100%;
            max-width: 28rem;
            text-align: center;
        }
        .logo {
            height: 2rem;
            width: auto;
            margin-bottom: 1.5rem;
        }
        .illustration {
            width: 8rem;
            height: auto;
            display: block;
            margin: 0 auto 1.5rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            color: #064e3b;
        }
        p {
            font-size: 0.9375rem;
            line-height: 1.5;
            color: #059669;
            margin: 0;
        }
        .message { margin-bottom: 0.5rem; }
        .cta {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.6rem 1.5rem;
            background: #059669;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            text-decoration: none;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
            transition: background 0.15s ease;
        }
        .cta:hover { background: #047857; }
        .cta:active { background: #065f46; }
        .cta:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 3px;
        }
        .footer {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <img class="logo" src="/images/logo/alalay-logo.png" alt="ALALAY logo">
        <img class="illustration" src="/images/illustration/error-403.svg" alt="Access Denied illustration">
        <h1>Access Denied</h1>
        <p class="message">You don't have access to this page.</p>
        <p>Contact the system administrator if you believe this is a mistake.</p>
        <!-- Hardcoded: the app root. Update manually if the public path ever changes. -->
        <a class="cta" href="/">Back to Home</a>
        <div class="footer">Municipality of General Mamerto Natividad, Nueva Ecija</div>
    </div>
</body>
</html>