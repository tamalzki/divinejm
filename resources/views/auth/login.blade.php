<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — Divine JM Foods</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand-deep:  #2c2c38;
            --accent:      #3b5bdb;
            --accent-h:    #2f4ac2;
            --border:      #e4e4e0;
            --text-p:      #1c1c1a;
            --text-s:      #44443e;
            --text-m:      #8a8a82;
            --bg-page:     #f5f5f2;
            --bg-card:     #ffffff;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            background: var(--bg-page);
            color: var(--text-p);
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }

        /* ── Card ── */
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }

        .login-card-bar {
            height: 4px;
            background: var(--brand-deep);
        }

        /* Header */
        .login-head {
            padding: 2rem 2.5rem 1.75rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .login-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: var(--brand-deep);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-logo i { font-size: 22px; color: #fff; }

        .login-head-text h1 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-p);
            line-height: 1.2;
        }

        .login-head-text p {
            font-size: .78rem;
            color: var(--text-m);
            margin-top: .2rem;
        }

        /* Body */
        .login-body { padding: 2.5rem 2.5rem 2.75rem; }

        /* Alert */
        .login-alert {
            padding: .6rem .9rem;
            border-radius: 6px;
            font-size: .78rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: flex-start;
            gap: .42rem;
            line-height: 1.4;
        }

        .login-alert i { flex-shrink: 0; margin-top: .06rem; }
        .login-alert.danger  { background: #fee2e2; color: #9b1c1c; }
        .login-alert.success { background: #dcfce7; color: #15622e; }

        /* Fields */
        .field { margin-bottom: 1.75rem; }

        .field label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-s);
            margin-bottom: .33rem;
        }

        .input-wrap { position: relative; }

        .input-wrap .ico {
            position: absolute;
            left: .75rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: .88rem;
            color: var(--text-m);
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: .8rem .9rem .8rem 2.3rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: .88rem;
            color: var(--text-p);
            background: var(--bg-card);
            outline: none;
            transition: border .12s, box-shadow .12s;
        }

        .input-wrap input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59,91,219,.1);
        }

        .input-wrap input.err { border-color: #dc2626; }
        .input-wrap input::placeholder { color: var(--text-m); }

        .show-pass {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            color: var(--text-m);
            font-size: .88rem;
            line-height: 1;
        }

        .show-pass:hover { color: var(--text-s); }

        .field-err {
            font-size: .72rem;
            color: #dc2626;
            margin-top: .22rem;
            display: block;
        }

        /* Extras */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.35rem;
        }

        .check-wrap {
            display: flex;
            align-items: center;
            gap: .38rem;
            cursor: pointer;
        }

        .check-wrap input[type="checkbox"] {
            width: 13px;
            height: 13px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .check-wrap span { font-size: .78rem; color: var(--text-s); }

        /* Submit */
        .btn-signin {
            width: 100%;
            padding: .72rem 1rem;
            background: var(--brand-deep);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: .88rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            transition: background .12s;
        }

        .btn-signin:hover  { background: var(--accent); }
        .btn-signin:active { opacity: .9; }

        /* Footer */
        .login-foot {
            padding: 1rem 2.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .38rem;
            font-size: .72rem;
            color: var(--text-m);
        }

        .login-foot i { color: var(--accent); }

        .login-note {
            margin-top: 1.1rem;
            font-size: .72rem;
            color: var(--text-m);
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-card-bar"></div>

    <div class="login-head">
        <div class="login-logo">
            <img src="{{ asset('images/divine-jm-logo.jpeg') }}"
                 alt=""
                 onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'bi bi-box-seam\'></i>'">
        </div>
        <div class="login-head-text">
            <h1>Divine JM Foods</h1>
            <p>Inventory &amp; Branch Management System</p>
        </div>
    </div>

    <div class="login-body">

        @if($errors->any())
        <div class="login-alert danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><strong>Login failed.</strong> {{ $errors->first() }}</div>
        </div>
        @endif

        @if(session('status'))
        <div class="login-alert success">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('status') }}</div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">Email address</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope ico"></i>
                    <input id="email"
                           type="email"
                           name="email"
                           class="{{ $errors->has('email') ? 'err' : '' }}"
                           value="{{ old('email') }}"
                           placeholder="you@divinejm.com"
                           required
                           autofocus
                           autocomplete="email">
                </div>
                @error('email')<span class="field-err">{{ $message }}</span>@enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock ico"></i>
                    <input id="password"
                           type="password"
                           name="password"
                           class="{{ $errors->has('password') ? 'err' : '' }}"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password">
                    <button type="button" class="show-pass" onclick="togglePass()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
                @error('password')<span class="field-err">{{ $message }}</span>@enderror
            </div>

            <div class="form-extras">
                <label class="check-wrap">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-signin">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign In
            </button>
        </form>
    </div>

    <div class="login-foot">
        <i class="bi bi-shield-check"></i>
        Secure access &nbsp;·&nbsp; &copy; {{ date('Y') }} Divine JM Foods
    </div>
</div>

<p class="login-note">Contact your administrator if you need access.</p>

<script>
function togglePass() {
    var inp  = document.getElementById('password');
    var icon = document.getElementById('eyeIcon');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
}
</script>

</body>
</html>