<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('school.name') }} · Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col items-center justify-center px-4 py-10">
        <a href="/" class="mb-6 flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-800 font-bold text-white">S</span><span class="font-bold text-blue-950">{{ config('school.name') }}</span></a>
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">{{ $slot }}</div>
    </div>
</body>
</html>
