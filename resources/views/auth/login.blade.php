<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - SISIR</title>

    {{-- Tailwind CDN (kalau kamu belum pakai Vite) --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
        {{-- KIRI: Branding --}}
        <div class="hidden lg:flex items-center justify-center bg-white">
            <div class="text-center px-10">
                <div class="mx-auto w-32 h-32 flex items-center justify-center">
                    <img src="{{ asset('images/SisirLogo.png') }}" alt="Logo Sisir" class="w-full h-full object-contain">
                </div>
                <h1 class="mt-6 text-3xl font-extrabold text-slate-900">SISIR</h1>
                <p class="mt-2 text-slate-600">PTPN 1 Regional 7</p>
                <p class="text-slate-500">Sistem Analytics & Monitoring Dashboard</p>
            </div>
        </div>

        {{-- KANAN: Form --}}
        <div class="flex items-center justify-center p-6">
            <div class="w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                <h2 class="text-2xl font-bold text-slate-900">Admin Login</h2>
                <p class="text-slate-500 mt-1">Sign in to access your dashboard</p>

                {{-- error message --}}
                @if(session('error'))
                    <div class="mt-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form class="mt-6 space-y-4" method="POST" action="/login">
                    @csrf

                    <div>
                        <label class="text-sm font-medium text-slate-700">Username</label>
                        <input name="username" type="text" placeholder="Enter your username"
                               class="mt-2 w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-300" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Password</label>
                        <input name="password" type="password" placeholder="Enter your password"
                               class="mt-2 w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-300" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="remember" type="checkbox" name="remember" class="rounded border-slate-300">
                        <label for="remember" class="text-sm text-slate-600">Remember me</label>
                    </div>

                    <button class="w-full py-3 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold">
                        Sign In to Dashboard
                    </button>
                </form>

                <p class="text-center text-xs text-slate-400 mt-6">
                    Protected by enterprise-grade security
                </p>

                <p class="text-center text-xs text-slate-400 mt-4">
                    © {{ date('Y') }} PTPN 1 Regional 7. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
