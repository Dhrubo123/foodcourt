<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>restaurent | Street Food Marketplace</title>
        @vite('resources/js/home/main.js')
    </head>
    <body>
        <script>
            window.__RESTAURENT_LINKS = {
                customerLogin: @json(route('customer.login')),
                customerRegister: @json(route('customer.register')),
                sellerRegister: @json(route('seller.register')),
                adminPanel: @json(url('/admin')),
            };
        </script>
        <div id="home-app"></div>
    </body>
</html>
