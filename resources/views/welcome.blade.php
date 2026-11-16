<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('school.name') }} · School portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-800">
    <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
        <a href="/" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-800 font-bold text-white">S</span><span class="font-bold text-blue-950">{{ config('school.name') }}</span></a>
        <div class="flex gap-2">@auth<a class="btn-primary" href="{{ route('dashboard') }}">Go to dashboard</a>@else<a class="btn-secondary" href="{{ route('login') }}">Sign in</a><a class="btn-primary" href="{{ route('register') }}">Student registration</a>@endauth</div>
    </header>
    <main class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-10 sm:px-8 lg:grid-cols-2 lg:pt-20">
        <div><p class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-800">School records, made simpler</p><h1 class="mt-6 max-w-xl text-4xl font-bold tracking-tight text-blue-950 sm:text-5xl">One clear place for learning, fees and attendance.</h1><p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">Keep student records together, track fee payments and balances, enter exam results, and help families stay informed from any phone.</p>
            <div class="mt-8 flex flex-wrap gap-3">@auth<a class="btn-primary" href="{{ route('dashboard') }}">Open my dashboard</a>@else<a class="btn-primary" href="{{ route('login') }}">Sign in to the portal</a><a class="btn-secondary" href="{{ route('register') }}">Register as a student</a>@endauth</div>
            <p class="mt-5 text-xs text-slate-500">For staff and parent accounts, contact your school administrator.</p>
        </div>
        <div class="rounded-3xl bg-blue-950 p-6 text-white shadow-2xl shadow-blue-950/20 sm:p-9">
            <div class="flex items-center justify-between"><div><p class="text-sm text-blue-200">A better school day</p><h2 class="mt-1 text-xl font-bold">Everything in view</h2></div><div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-400 text-xl font-bold text-blue-950">✓</div></div>
            <div class="mt-7 space-y-3">
                @foreach([['01','Student records','Find students and class details quickly'],['02','Fee tracking','See payments, balances and receipts'],['03','Results & attendance','Share progress with families'],['04','Parent updates','Prepare for SMS notifications']] as [$number,$title,$detail])
                    <div class="flex gap-4 rounded-2xl border border-white/10 bg-white/5 p-4"><span class="text-sm font-bold text-sky-300">{{ $number }}</span><div><h3 class="font-semibold">{{ $title }}</h3><p class="mt-1 text-sm text-blue-200">{{ $detail }}</p></div></div>
                @endforeach
            </div>
        </div>
    </main>
    <footer class="border-t border-slate-100 py-5 text-center text-xs text-slate-500">{{ config('school.name') }} · Secure school portal</footer>
</body>
</html>
