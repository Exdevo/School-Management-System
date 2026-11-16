<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('school.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false"></div>
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-blue-950 text-white transition-transform duration-200 lg:translate-x-0">
            <a href="{{ route('dashboard') }}" class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-400 text-lg font-bold text-blue-950">S</span>
                <span><span class="block text-sm font-bold">{{ config('school.name') }}</span><span class="text-xs text-blue-200">School portal</span></span>
            </a>
            <nav class="flex-1 space-y-1 px-3 py-5 text-sm">
                <a class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : '' }}" href="{{ route('dashboard') }}">Overview</a>
                @if(in_array(auth()->user()->role, ['admin', 'teacher']))
                    <a class="nav-item {{ request()->routeIs('students.*') ? 'nav-item-active' : '' }}" href="{{ route('students.index') }}">Students</a>
                    <a class="nav-item {{ request()->routeIs('classes.*') ? 'nav-item-active' : '' }}" href="{{ route('classes.index') }}">Classes</a>
                    <a class="nav-item {{ request()->routeIs('subjects.*') ? 'nav-item-active' : '' }}" href="{{ route('subjects.index') }}">Subjects</a>
                    <a class="nav-item {{ request()->routeIs('results.*') ? 'nav-item-active' : '' }}" href="{{ route('results.index') }}">Results</a>
                    <a class="nav-item {{ request()->routeIs('attendance.*') ? 'nav-item-active' : '' }}" href="{{ route('attendance.index') }}">Attendance</a>
                @endif
                @if(in_array(auth()->user()->role, ['admin', 'student', 'parent']))
                    <a class="nav-item {{ request()->routeIs('fees.*') ? 'nav-item-active' : '' }}" href="{{ route('fees.index') }}">School fees</a>
                @endif
                @if(auth()->user()->role === 'admin')
                    <a class="nav-item {{ request()->routeIs('users.*') ? 'nav-item-active' : '' }}" href="{{ route('users.index') }}">Staff & parents</a>
                @endif
                <a class="nav-item {{ request()->routeIs('profile.*') ? 'nav-item-active' : '' }}" href="{{ route('profile.edit') }}">My profile</a>
            </nav>
            <div class="border-t border-white/10 p-4">
                <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="mb-3 text-xs capitalize text-blue-200">{{ auth()->user()->role }}</p>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="w-full rounded-lg px-3 py-2 text-left text-sm text-blue-100 hover:bg-white/10">Sign out</button>
                </form>
            </div>
        </aside>
        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-7">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="text-sm font-semibold text-slate-700">{{ $heading ?? 'School overview' }}</span>
                </div>
                <span class="hidden text-sm text-slate-500 sm:block">{{ now()->format('l, d M Y') }}</span>
            </header>
            <main class="mx-auto max-w-7xl p-4 sm:p-7">
                @if(session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Please review the form:</p>
                        <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
