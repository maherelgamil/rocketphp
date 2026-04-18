<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Rocket') }}</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', config('rocket.assets.js_entry')])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
