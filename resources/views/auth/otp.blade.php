<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP - CineMax</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:       #0d0d0f;
            --surface:  #141417;
            --surface2: #1c1c21;
            --accent:   #e8340a;
            --accent2:  #ff6b35;
            --gold:     #f5c518;
            --text:     #f0eff4;
            --muted:    rgba(240,239,244,0.5);
            --border:   rgba(240,239,244,0.08);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            /* subtle radial glow behind card */
            background-image: radial-gradient(ellipse 60% 50% at 50% 50%, rgba(232,52,10,0.07) 0%, transparent 70%);
        }

        /* ── CARD ── */
        .otp-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2.75rem 2.5rem 2.25rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(232,52,10,0.06);
            animation: slideUp 0.4s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── ICON BADGE ── */
        .otp-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(232,52,10,0.12);
            border: 1px solid rgba(232,52,10,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
            color: var(--accent);
            animation: pulse-glow 2.5s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(232,52,10,0); }
            50%       { box-shadow: 0 0 0 10px rgba(232,52,10,0.08); }
        }

        /* ── HEADINGS ── */
        .otp-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.2rem;
            letter-spacing: 2px;
            color: var(--accent);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .otp-subtitle {
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .otp-subtitle strong {
            color: var(--text);
            font-weight: 600;
        }

        /* ── DIGIT BOXES ── */
        .digit-row {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .digit-box {
            width: 52px;
            height: 60px;
            background: var(--surface2);
            border: 1.5px solid var(--border);
            border-radius: 0.6rem;
            color: var(--text);
            font-size: 1.6rem;
            font-weight: 600;
            text-align: center;
            caret-color: var(--accent);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            font-family: 'DM Sans', sans-serif;
        }

        .digit-box:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,52,10,0.18);
            background: rgba(232,52,10,0.05);
        }

        .digit-box.filled {
            border-color: rgba(232,52,10,0.5);
            color: var(--text);
        }

        /* hidden real input */
        #otp-hidden { display: none; }

        /* ── ALERT ── */
        .otp-alert {
            border-radius: 0.6rem;
            padding: 0.85rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .otp-alert.success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #4ade80;
        }

        .otp-alert.info {
            background: rgba(245,197,24,0.1);
            border: 1px solid rgba(245,197,24,0.3);
            color: #facc15;
        }
        .otp-alert.error {
            background: rgba(220,53,69,0.1);
            border: 1px solid rgba(220,53,69,0.3);
            color: #f87171;
        }

        /* ── BUTTON ── */
        .btn-verify {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 0.9rem 1.5rem;
            border-radius: 0.6rem;
            font-weight: 700;
            font-size: 1rem;
            font-family: 'DM Sans', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            letter-spacing: 0.5px;
        }

        .btn-verify:hover {
            background: #c42908;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(232,52,10,0.3);
        }

        .btn-verify:active { transform: translateY(0); }

        /* ── FOOTER LINKS ── */
        .otp-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .otp-footer a {
            color: var(--muted);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .otp-footer a:hover { color: var(--accent2); }

        /* ── BRAND LINK ── */
        .otp-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 2rem;
            text-decoration: none;
        }

        .otp-brand-text {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.6rem;
            color: var(--accent);
            letter-spacing: 2px;
        }

        .otp-brand-dot {
            width: 7px;
            height: 7px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.7); }
        }
    </style>
</head>
<body>
    <div class="otp-card">

        {{-- Brand --}}
        <a class="otp-brand" href="/">
            <i class="bi bi-play-circle-fill" style="color:var(--accent);font-size:1.4rem;"></i>
            <span class="otp-brand-text">CINEMAX</span>
            <span class="otp-brand-dot"></span>
        </a>

        {{-- Icon --}}
        <div class="otp-icon-wrap">
            <i class="bi bi-envelope-check"></i>
        </div>

        <h1 class="otp-title">Verify Email</h1>
        <p class="otp-subtitle">
            We sent a 6-digit code to<br>
            <strong>{{ session('otp_email') }}</strong>
        </p>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="otp-alert success">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="otp-alert info">
                <i class="bi bi-info-circle-fill"></i>
                {{ session('info') }}
            </div>
        @endif
        @if($errors->has('otp'))
            <div class="otp-alert error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ $errors->first('otp') }}
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
            @csrf
            <input type="hidden" name="otp" id="otp-hidden">

            {{-- 6 visible digit boxes --}}
            <div class="digit-row" id="digitRow">
                @for($i = 0; $i < 6; $i++)
                    <input class="digit-box" type="text" inputmode="numeric" maxlength="1"
                           data-index="{{ $i }}" autocomplete="off" aria-label="Digit {{ $i+1 }}">
                @endfor
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                <i class="bi bi-shield-check me-2"></i> Verify Code
            </button>
        </form>

        <div class="otp-footer">
            @php
                // If the user already exists and is pending → came from login flow
                $existingUser = \App\Models\User::where('email', session('otp_email'))->first();
                $fromLogin = $existingUser && $existingUser->created_at->diffInSeconds(now()) > 30;
            @endphp
            <a href="{{ $fromLogin ? route('login') : route('register') }}">
                <i class="bi bi-arrow-left me-1"></i>
                {{ $fromLogin ? 'Back to Login' : 'Back to Register' }}
            </a>
        </div>
    </div>

    <script>
        const boxes  = Array.from(document.querySelectorAll('.digit-box'));
        const hidden = document.getElementById('otp-hidden');
        const form   = document.getElementById('otpForm');

        function syncHidden() {
            hidden.value = boxes.map(b => b.value).join('');
        }

        boxes.forEach((box, idx) => {
            box.addEventListener('input', () => {
                // Strip non-digits
                box.value = box.value.replace(/\D/g, '').slice(-1);
                box.classList.toggle('filled', box.value !== '');
                syncHidden();
                // Auto-advance
                if (box.value && idx < boxes.length - 1) {
                    boxes[idx + 1].focus();
                }
            });

            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && idx > 0) {
                    boxes[idx - 1].focus();
                    boxes[idx - 1].value = '';
                    boxes[idx - 1].classList.remove('filled');
                    syncHidden();
                }
                if (e.key === 'ArrowLeft' && idx > 0) boxes[idx - 1].focus();
                if (e.key === 'ArrowRight' && idx < boxes.length - 1) boxes[idx + 1].focus();
            });

            box.addEventListener('paste', e => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                text.split('').slice(0, 6).forEach((ch, i) => {
                    if (boxes[i]) { boxes[i].value = ch; boxes[i].classList.add('filled'); }
                });
                syncHidden();
                const next = boxes[Math.min(text.length, 5)];
                if (next) next.focus();
            });
        });

        form.addEventListener('submit', e => {
            syncHidden();
            if (hidden.value.length < 6) {
                e.preventDefault();
                boxes[hidden.value.length]?.focus();
            }
        });

        // Focus first empty box on load
        (boxes.find(b => !b.value) || boxes[0])?.focus();
    </script>
</body>
</html>