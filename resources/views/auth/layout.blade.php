<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIMS') — Student Information Management System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:       #1677f2;
            --primary-dark:  #125fca;
            --primary-light: #429cf5;
            --accent:        #655bd8;
            --success:       #16a34a;
            --error:         #dc2626;
            --warning:       #d97706;
            --bg:            #f3f5fb;
            --card:          #ffffff;
            --border:        #d1d5db;
            --text:          #111827;
            --muted:         #6b7280;
            --input-bg:      #f9fafb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(ellipse at top right, rgba(101,91,216,.45), transparent 38%), #191f32;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        /* ── Card ── */
        .auth-wrapper {
            width: 100%;
            max-width: 460px;
        }

        .brand {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #fff;
        }
        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            margin-bottom: 0.75rem;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .brand-icon svg { width: 34px; height: 34px; fill: #fff; }
        .brand h1 { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.5px; }
        .brand p  { font-size: 0.85rem; opacity: 0.8; margin-top: 0.25rem; }

        .card {
            background: var(--card);
            border-radius: 14px;
            padding: 2.25rem 2.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        /* ── Tabs ── */
        .tab-row {
            display: flex;
            gap: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.75rem;
        }
        .tab-row a {
            flex: 1;
            text-align: center;
            padding: 0.65rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--muted);
            background: #f3f4f6;
            transition: all 0.2s;
        }
        .tab-row a.active {
            background: var(--primary);
            color: #fff;
        }
        .tab-row a:first-child { border-radius: 9px 0 0 9px; }
        .tab-row a:last-child  { border-radius: 0 9px 9px 0; }

        /* ── Heading ── */
        .card-heading { margin-bottom: 1.5rem; }
        .card-heading h2 { font-size: 1.35rem; font-weight: 700; color: var(--text); }
        .card-heading p  { font-size: 0.85rem; color: var(--muted); margin-top: 0.3rem; }

        /* ── Form ── */
        .form-group { margin-bottom: 1.1rem; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.4rem;
            letter-spacing: 0.01em;
        }

        .input-wrap {
            position: relative;
        }
        .input-wrap .icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }
        .input-wrap .icon svg { width: 17px; height: 17px; }

        .form-control {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 2.6rem;
            font-size: 0.9rem;
            font-family: inherit;
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
            background: #fff;
        }
        .form-control.is-invalid {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
        }

        .error-msg {
            font-size: 0.78rem;
            color: var(--error);
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* ── Password toggle ── */
        .toggle-pw {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 0;
            line-height: 1;
        }
        .toggle-pw:hover { color: var(--primary); }
        .toggle-pw svg { width: 17px; height: 17px; }

        /* ── Remember + Forgot row ── */
        .row-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.83rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }
        .remember-label input[type="checkbox"] {
            accent-color: var(--primary);
            width: 15px;
            height: 15px;
        }
        .forgot-link {
            font-size: 0.83rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-link:hover { text-decoration: underline; }

        /* ── Submit button ── */
        .btn-primary {
            width: 100%;
            padding: 0.8rem;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            color: #fff;
            background: var(--primary);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            letter-spacing: 0.01em;
        }
        .btn-primary:hover   { opacity: 0.92; }
        .btn-primary:active  { transform: scale(0.99); }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: var(--muted);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--border);
        }

        /* ── Password strength bar ── */
        .strength-bar {
            margin-top: 0.4rem;
            height: 4px;
            border-radius: 99px;
            background: var(--border);
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.3s, background 0.3s;
            width: 0;
        }
        .strength-label {
            font-size: 0.72rem;
            color: var(--muted);
            margin-top: 0.25rem;
        }

        /* ── Alert flash ── */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ── Password requirements ── */
        .pw-reqs {
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ── Footer note ── */
        .footer-note {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.82rem;
            color: var(--muted);
        }
        .footer-note a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .footer-note a:hover { text-decoration: underline; }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .card { padding: 1.75rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">

        <!-- Brand Header -->
        <div class="brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.18L19.5 9 12 12.82 4.5 9 12 5.18zM5 13.18V17l7 4 7-4v-3.82L12 17l-7-3.82z"/>
                </svg>
            </div>
            <h1>SIMS</h1>
            <p>Student Information Management System</p>
        </div>

        <!-- Card -->
        <div class="card">
            @yield('content')
        </div>

    </div>

    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-pw').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                const isText = input.type === 'text';
                input.type = isText ? 'password' : 'text';
                btn.querySelector('.eye-on').classList.toggle('hidden', !isText);
                btn.querySelector('.eye-off').classList.toggle('hidden', isText);
            });
        });

        // Strength meter
        const pwInput = document.getElementById('password');
        if (pwInput) {
            pwInput.addEventListener('input', () => {
                const val = pwInput.value;
                const fill  = document.getElementById('strength-fill');
                const label = document.getElementById('strength-label');
                if (!fill) return;
                let score = 0;
                if (val.length >= 8)                       score++;
                if (/[A-Z]/.test(val))                     score++;
                if (/[0-9]/.test(val))                     score++;
                if (/[^A-Za-z0-9]/.test(val))              score++;
                const levels = [
                    { pct: '0%',   color: 'transparent', text: '' },
                    { pct: '25%',  color: '#ef4444', text: 'Weak' },
                    { pct: '50%',  color: '#f97316', text: 'Fair' },
                    { pct: '75%',  color: '#eab308', text: 'Good' },
                    { pct: '100%', color: '#22c55e', text: 'Strong' },
                ];
                const lvl = levels[score];
                fill.style.width    = lvl.pct;
                fill.style.background = lvl.color;
                label.textContent   = lvl.text;
                label.style.color   = lvl.color;
            });
        }
    </script>

    @yield('scripts')
</body>
</html>
