<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkyCast PRO</title>
    <link rel="manifest" href="/build/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <link rel="apple-touch-icon" href="/icons/pwa-192x192.png">

    <link rel="icon" type="image/png" href="/icons/pwa-192x192.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div id="app"></div>
</body>

</html>