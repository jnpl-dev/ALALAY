<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ALALAY — Maintenance</title>
    <style>
        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #022c22;
            background:
                radial-gradient(60rem 32rem at 50% -12%, rgba(16, 185, 129, 0.10), transparent 62%),
                radial-gradient(48rem 28rem at 115% 115%, rgba(16, 185, 129, 0.08), transparent 60%),
                #f0fdf4;
        }
        .wrapper {
            width: 100%;
            max-width: 28rem;
            text-align: center;
        }
        .logo {
            height: 2rem;
            width: auto;
            margin-bottom: 2.5rem;
            opacity: 0.92;
        }
        .ring {
            position: relative;
            width: 5.25rem;
            height: 5.25rem;
            margin: 0 auto 2rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ring::before,
        .ring::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 2px solid rgba(16, 185, 129, 0.35);
            animation: ping 2.6s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        .ring::after {
            animation-delay: 1.3s;
        }
        .dot {
            width: 1.125rem;
            height: 1.125rem;
            border-radius: 9999px;
            background: #10b981;
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.18);
        }
        @keyframes ping {
            0% { transform: scale(0.55); opacity: 0.8; }
            80%, 100% { transform: scale(1.65); opacity: 0; }
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.375rem 0.9rem;
            border-radius: 9999px;
            background: #d1fae5;
            color: #047857;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        h1 {
            font-size: clamp(1.75rem, 4vw + 0.75rem, 2.5rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin: 1.25rem 0 0;
            color: #022c22;
        }
        .message {
            font-size: 0.9375rem;
            line-height: 1.65;
            color: #047857;
            margin: 0.9rem auto 0;
            max-width: 34ch;
        }
        .cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.75rem;
            padding: 0.7rem 1.5rem;
            background: #059669;
            color: #ffffff;
            border: 0;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 10px 25px -8px rgba(16, 185, 129, 0.45);
            transition: background 0.15s ease, transform 0.15s ease;
        }
        .cta svg {
            width: 1.125rem;
            height: 1.125rem;
        }
        .cta:hover { background: #047857; }
        .cta:active { transform: translateY(1px); }
        .cta:focus-visible {
            outline: 2px solid #059669;
            outline-offset: 3px;
        }
        .footer {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #047857;
            opacity: 0.75;
        }
        @media (prefers-reduced-motion: reduce) {
            .ring::before,
            .ring::after {
                animation: none;
                opacity: 0.25;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <img class="logo" src="/images/logo/alalay-logo.png" alt="ALALAY logo">
        <div class="ring" aria-hidden="true"><span class="dot"></span></div>
        <span class="pill">Scheduled maintenance</span>
        <h1>We'll be back shortly.</h1>
        <p class="message">The ALALAY system is undergoing maintenance. Your applications and records are safe. Please check back in a few minutes.</p>
        <button class="cta" type="button" onclick="window.location.reload()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Try again
        </button>
        <div class="footer">Municipality of General Mamerto Natividad, Nueva Ecija</div>
    </div>
</body>
</html>