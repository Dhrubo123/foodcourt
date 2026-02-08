<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Seller Registration</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap');
            :root {
                --bg: #f6f1ea;
                --panel: #ffffff;
                --text: #1d1a14;
                --muted: #6f675c;
                --primary: #e4572e;
                --border: #eadfd2;
                --shadow: 0 24px 60px rgba(30, 22, 10, 0.18);
            }
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
                background: radial-gradient(circle at top right, #ffe6d6 0%, transparent 45%),
                    radial-gradient(circle at bottom left, #d9f2f5 0%, transparent 45%), var(--bg);
                color: var(--text);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 32px 16px;
            }
            .card {
                width: min(920px, 100%);
                background: var(--panel);
                border-radius: 28px;
                border: 1px solid var(--border);
                box-shadow: var(--shadow);
                padding: 32px;
            }
            .title {
                font-family: 'Playfair Display', serif;
                font-size: 28px;
                margin-bottom: 6px;
            }
            .subtitle {
                color: var(--muted);
                margin-bottom: 20px;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px;
            }
            .field {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            label {
                font-size: 13px;
                color: var(--muted);
            }
            input,
            select {
                padding: 12px 14px;
                border-radius: 12px;
                border: 1px solid var(--border);
                font-size: 14px;
            }
            .actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 18px;
                gap: 10px;
            }
            .btn {
                padding: 12px 18px;
                border-radius: 999px;
                border: none;
                background: var(--primary);
                color: #fff;
                font-weight: 600;
                cursor: pointer;
            }
            .message {
                margin: 12px 0;
                color: var(--primary);
                font-weight: 600;
            }
            .errors {
                margin: 12px 0;
                color: #a62d1f;
                font-weight: 600;
            }
            @media (max-width: 600px) {
                .card {
                    padding: 22px;
                }
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="title">Register your food cart</div>
            <div class="subtitle">Submit details to get approved and start selling on restaurent.</div>

            @if (session('success'))
                <div class="message">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="errors">
                    Please fix the errors and try again.
                </div>
            @endif

            <form method="POST" action="{{ url('/register-seller') }}">
                @csrf
                <div class="grid">
                    <div class="field">
                        <label>Owner name</label>
                        <input name="owner_name" value="{{ old('owner_name') }}" required />
                    </div>
                    <div class="field">
                        <label>Owner email</label>
                        <input name="owner_email" type="email" value="{{ old('owner_email') }}" />
                    </div>
                    <div class="field">
                        <label>Owner phone</label>
                        <input name="owner_phone" value="{{ old('owner_phone') }}" />
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input name="password" type="password" required />
                    </div>
                    <div class="field">
                        <label>Seller name</label>
                        <input name="name" value="{{ old('name') }}" required />
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="cart" @selected(old('type') === 'cart')>Cart</option>
                            <option value="food_court" @selected(old('type') === 'food_court')>Food Court</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input name="phone" value="{{ old('phone') }}" />
                    </div>
                    <div class="field">
                        <label>Address</label>
                        <input name="address" value="{{ old('address') }}" />
                    </div>
                    <div class="field">
                        <label>Latitude</label>
                        <input name="lat" type="number" step="0.000001" value="{{ old('lat') }}" />
                    </div>
                    <div class="field">
                        <label>Longitude</label>
                        <input name="lng" type="number" step="0.000001" value="{{ old('lng') }}" />
                    </div>
                    <div class="field">
                        <label>Open time (HH:mm)</label>
                        <input name="open_time" placeholder="09:00" value="{{ old('open_time') }}" />
                    </div>
                    <div class="field">
                        <label>Close time (HH:mm)</label>
                        <input name="close_time" placeholder="22:00" value="{{ old('close_time') }}" />
                    </div>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Submit Registration</button>
                </div>
            </form>
        </div>
    </body>
</html>
