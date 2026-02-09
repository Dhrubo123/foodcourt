<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Customer Account</title>
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
                width: min(620px, 100%);
                background: var(--panel);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 24px;
            }
            h1 { margin: 0 0 8px; font-size: 24px; }
            p { margin: 0 0 14px; color: var(--muted); }
            .row { margin-bottom: 8px; }
            .row strong { display: inline-block; width: 90px; }
            .actions { margin-top: 16px; display: flex; gap: 10px; }
            .credit {
                margin-top: 18px;
                text-align: center;
                font-size: 12px;
                letter-spacing: 0.04em;
                color: var(--muted);
                font-weight: 700;
            }
            a, button {
                border: none;
                background: var(--primary);
                color: #fff;
                padding: 10px 16px;
                border-radius: 999px;
                cursor: pointer;
                font-weight: 600;
                text-decoration: none;
            }
            .ghost {
                background: transparent;
                border: 1px solid var(--primary);
                color: var(--primary);
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Customer Account</h1>
            <p>Welcome. You can now place orders from web and mobile using the same account.</p>

            <div class="row"><strong>Name:</strong> {{ $user->name }}</div>
            <div class="row"><strong>Email:</strong> {{ $user->email ?? '-' }}</div>
            <div class="row"><strong>Phone:</strong> {{ $user->phone ?? '-' }}</div>

            <div class="actions">
                <a href="/" class="ghost">Go Home</a>
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit">Logout</button>
                </form>
            </div>

            <p class="credit">Developed by APARUP BARUA</p>
        </div>
    </body>
</html>
