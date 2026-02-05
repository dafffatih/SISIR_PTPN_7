<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - SISIR</title>

  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen">
  <div class="min-h-screen relative overflow-hidden">

    {{-- Background image --}}
    <img
      src="{{ asset('images/login-bg.jpg') }}"
      alt=""
      class="absolute inset-0 w-full h-full object-cover object-center
         select-none pointer-events-none
         blur-[1px] scale-100"
      draggable="false"
    />


    {{-- Overlay putih tipis biar tidak silau --}}
    <div class="absolute inset-0 bg-black/40"></div>

    



    <div class="relative min-h-screen flex items-center justify-center px-6">
      <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

        {{-- KIRI: BRANDING --}}
        <div class="hidden lg:flex items-center justify-center">
          <div class="flex flex-col items-center text-center">
            <img
              src="{{ asset('images/SisirWordmark.png') }}"
              alt="Logo SISIR"
              class="w-[420px] h-auto object-contain"
              draggable="false"
            >


          <p class="mt-3 text-white text-lg font-semibold leading-snug">
            Sales and Inventories Statistic of Rubber
          </p>
          <p class="mt-0.5 text-white/90 text-base tracking-wide">
            PTPN 1 Regional 7
          </p>


          </div>
        </div>

        {{-- KANAN: LOGIN FORM --}}
        <div class="flex justify-center lg:justify-end">
          <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-slate-900">Admin Login</h2>
            <p class="text-slate-500 mt-1">Sign in to access your dashboard</p>

            @if(session('error'))
              <div class="mt-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm">
                {{ session('error') }}
              </div>
            @endif

            <form class="mt-6 space-y-4" method="POST" action="/login">
              @csrf
              <div>
                <label class="text-sm font-medium text-slate-700">Username</label>
                <input
                  name="username"
                  type="text"
                  placeholder="Enter your username"
                  class="mt-2 w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-300"
                  autocomplete="username"
                />
              </div>

              <div>
                <label class="text-sm font-medium text-slate-700">Password</label>
                <input
                  name="password"
                  type="password"
                  placeholder="Enter your password"
                  class="mt-2 w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-orange-300"
                  autocomplete="current-password"
                />
              </div>

              <div class="flex items-center gap-2">
                <input id="remember" type="checkbox" name="remember" class="rounded border-slate-300">
                <label for="remember" class="text-sm text-slate-600">Remember me</label>
              </div>

              <button
                  type="submit"
                  class="w-full py-3 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold transition">
                  Sign In to Dashboard
              </button>
            </form>

            <p class="text-center text-xs text-slate-400 mt-6">
              Protected by enterprise-grade security
            </p>

            <p class="text-center text-xs text-slate-400 mt-4">
              © {{ date('Y') }} PTPN 1 Regional 7 Bagian MAP. All rights reserved.
            </p>
          </div>
        </div>

      </div>
    </div>

    {{-- CREDIT SECTION --}}
    <div class="absolute bottom-6 right-6 z-20 pointer-events-none">
        <div class="px-4 py-2 rounded-xl bg-black/10 backdrop-blur-sm border border-white/5 shadow-md text-right">
            <p class="text-xs font-medium text-slate-200/70">
                Develop By
            </p>
            <p class="text-sm font-bold text-orange-200/70 tracking-wide">
                Wahyu SAP, Dafffatih, Zaky t 
            </p> <!-- JIKA KALIAN MELANJUTKAN PROJEK INI, SILAHKAN TAMBAHKAN NAMA KALIAN DI SINI, NAMA KAMI JANGAN DIHAPUS. -->
        </div>
    </div>


  </div> 
</body>
</html>