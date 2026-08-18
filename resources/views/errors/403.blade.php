<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ALALAY — Access Denied</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 3vh, 2rem);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: linear-gradient(135deg, #ecfdf5, #ffffff, #ecfdf5);
            color: #064e3b;
        }
        .wrapper {
            width: 100%;
            max-width: 48rem;
            margin: auto;
            text-align: center;
        }
        .logo {
            height: 2.5rem;
            width: auto;
            margin-bottom: clamp(1.25rem, 3vh, 2.25rem);
        }
        .illustration {
            width: min(15rem, 40vw, 30vh);
            height: auto;
            display: block;
            margin: 0 auto clamp(1.25rem, 3vh, 2.5rem);
        }
        h1 {
            font-size: clamp(1.5rem, 3.5vw + 0.5rem, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            margin: 0 0 0.625rem;
            color: #064e3b;
        }
        p {
            font-size: clamp(0.9375rem, 1vw + 0.5rem, 1.0625rem);
            line-height: 1.55;
            color: #059669;
            margin: 0;
        }
        .message { margin-bottom: 0.5rem; }
        .actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.875rem;
            margin-top: clamp(1.5rem, 4vh, 2.25rem);
        }
        .cta {
            display: inline-block;
            padding: 0.75rem 1.75rem;
            background: #059669;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            text-decoration: none;
            cursor: pointer;
            border: 0;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
            transition: background 0.15s ease, transform 0.15s ease;
        }
        .cta:hover { background: #047857; }
        .cta:active { transform: translateY(1px); }
        .cta:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 3px;
        }
        .ghost {
            display: inline-block;
            padding: 0.25rem;
            background: none;
            border: 0;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            color: #047857;
            text-decoration: none;
            cursor: pointer;
        }
        .ghost:hover { color: #065f46; text-decoration: underline; }
        .ghost:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 3px;
            border-radius: 0.25rem;
        }
        .footer {
            margin-top: clamp(1.5rem, 4vh, 2.5rem);
            font-size: 0.75rem;
            color: #10b981;
        }
        @media (max-height: 600px) {
            .logo { height: 2rem; margin-bottom: 1rem; }
            .illustration { width: min(7rem, 28vh); margin-bottom: 1rem; }
            h1 { font-size: 1.375rem; margin-bottom: 0.25rem; }
            .actions { margin-top: 1rem; gap: 0.625rem; }
            .footer { margin-top: 1rem; }
        }
    </style>
</head>
<body>
    @php
        $dashboardUrl = url('/');
        if (auth()->check()) {
            $user = auth()->user();
            $dashboardUrl = match ($user->role) {
                'admin' => route('admin.dashboard'),
                'aics_staff' => route('aics.dashboard'),
                'mswdo' => route('mswdo.dashboard'),
                'accountant' => route('accountant.dashboard'),
                'treasurer' => route('treasurer.dashboard'),
                'internal_audit' => route('internal-audit.dashboard'),
                'budget_officer' => route('budget-office.dashboard'),
                default => route('dashboard'),
            };
        }
    @endphp
    <div class="wrapper">
        <img class="logo" src="/images/logo/alalay-logo.png" alt="ALALAY logo">
        <img class="illustration" src="/images/illustration/error-403.svg" alt="Access Denied illustration">
        <h1>Access Denied</h1>
        <p class="message">You don't have access to this page.</p>
        <p>Contact the system administrator if you believe this is a mistake.</p>
        <div class="actions">
            @auth
                <a class="cta" href="{{ $dashboardUrl }}">Back to My Dashboard</a>
                <a class="ghost" href="{{ url('/') }}">Back to Home</a>
            @else
                <a class="cta" href="{{ url('/') }}">Back to Home</a>
            @endauth
        </div>
        <div class="footer">Municipality of General Mamerto Natividad, Nueva Ecija</div>
    </div>
</body>
</html>