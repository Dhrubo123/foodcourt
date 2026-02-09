<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Customer Register</title>
        <style>
            :root {
                --bg: #f4f9f4;
                --panel: #fff;
                --border: #d8e8d8;
                --text: #173118;
                --muted: #4f6c50;
                --primary: #2f9f62;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                font-family: Segoe UI, Arial, sans-serif;
                background: var(--bg);
                color: var(--text);
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 20px;
            }
            .card {
                width: min(520px, 100%);
                background: var(--panel);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 24px;
            }
            h1 { margin: 0 0 8px; font-size: 24px; }
            p { margin: 0 0 18px; color: var(--muted); }
            .field { margin-bottom: 12px; }
            label { display: block; font-size: 13px; margin-bottom: 5px; color: var(--muted); }
            input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--border);
                border-radius: 10px;
                font-size: 14px;
            }
            .errors { color: #b33a3a; font-size: 13px; margin: 8px 0 12px; }
            .actions { display: flex; justify-content: flex-end; }
            button {
                border: none;
                background: var(--primary);
                color: #fff;
                padding: 10px 16px;
                border-radius: 999px;
                cursor: pointer;
                font-weight: 600;
            }
            .link { margin-top: 14px; font-size: 14px; color: var(--muted); }
            .link a { color: var(--primary); text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Create Customer Account</h1>
            <p>Register once and order from nearby food carts and food courts.</p>

            @if ($errors->any())
                <div class="errors">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('customer.register') }}">
                @csrf
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name') }}" required />
                </div>
                <div class="field">
                    <label>Email (optional)</label>
                    <input name="email" type="email" value="{{ old('email') }}" />
                </div>
                <div class="field">
                    <label>Phone (optional)</label>
                    <input name="phone" value="{{ old('phone') }}" />
                </div>
                <div class="field">
                    <label>Password</label>
                    <input name="password" type="password" required />
                </div>
                <div class="field">
                    <label>Confirm Password</label>
                    <input name="password_confirmation" type="password" required />
                </div>
                <div class="actions">
                    <button type="submit">Register</button>
                </div>
            </form>

            <div class="link">
                Already have account? <a href="{{ route('customer.login') }}">Login</a>
            </div>
        </div>
    </body>
</html>

