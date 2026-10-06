<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerbang Akses Lembaga - Taqreer Multi-Tenant</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 sm:p-6 bg-slate-950 text-slate-100" x-data="{ mode: 'token' }">
    <div class="w-full max-w-md space-y-6">
        <!-- Logo & Header (Logo can be clicked to toggle Super Admin login) -->
        <div class="text-center">
            <button type="button" @click="mode = (mode === 'token' ? 'admin' : 'token')"
                    title="Akses Platform"
                    class="w-14 h-14 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 mx-auto flex items-center justify-center font-extrabold text-white text-2xl shadow-xl shadow-emerald-500/25 mb-4 transition cursor-pointer select-none">
                T
            </button>
            <h1 class="text-2xl font-bold text-white tracking-tight">Taqreer Multi-Tenant</h1>
            <p class="text-xs text-slate-400 mt-1" x-show="mode === 'token'">Sistem Pelaporan & Evaluasi Santri Berbasis Lembaga Mandiri</p>
            <p class="text-xs text-emerald-400 mt-1 font-semibold" x-show="mode === 'admin'" x-cloak>Portal Khusus Super Admin Platform</p>
        </div>

        <!-- 1. Form Token Lembaga (Default) -->
        <div x-show="mode === 'token'" class="bg-white text-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-100/10 space-y-6">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Akses Lembaga
                </div>
                <h2 class="text-lg font-bold text-slate-900">Masukkan Token Lembaga</h2>
                <p class="text-xs text-slate-500 mt-0.5">Masukkan kode unik lembaga Anda untuk menuju portal login.</p>
            </div>

            <!-- Flash Messages -->
            @if(session('info'))
                <div class="p-3.5 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-medium">
                    {{ session('info') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('gateway.verify') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kode / Token Lembaga</label>
                    <div class="relative">
                        <input type="text" name="token" id="token" value="{{ old('token') }}" required autofocus
                            class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 text-base font-mono font-bold uppercase tracking-wider text-slate-900 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition placeholder:normal-case placeholder:font-normal placeholder:text-sm placeholder:text-slate-400"
                            placeholder="Contoh: TAQREER-DEMO"
                            style="text-transform: uppercase;">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-emerald-600/25 transition flex items-center justify-center gap-2">
                    <span>Lanjutkan &rarr;</span>
                </button>
            </form>

            <!-- Quick Demo Portal & Direct Login -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <a href="{{ route('gateway.direct', 'TAQREER-DEMO') }}" class="w-full py-2.5 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-semibold flex items-center justify-between transition border border-emerald-200">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Coba Lembaga Demo (TAQREER-DEMO)</span>
                    </div>
                    <span>&rarr;</span>
                </a>

                <div class="flex items-center justify-between text-xs pt-1 px-1">
                    <a href="{{ route('login') }}" class="text-slate-500 hover:text-emerald-600 transition">
                        Form Login Reguler
                    </a>
                    <button type="button" @click="mode = 'admin'" class="text-slate-500 hover:text-slate-900 font-medium transition">
                        Akses Super Admin Platform
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Form Login Super Admin (Hidden, revealed by clicking logo or link) -->
        <div x-show="mode === 'admin'" x-cloak class="bg-white text-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-100/10 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-slate-900 text-emerald-400 text-[11px] font-semibold mb-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Master Control
                    </div>
                    <h2 class="text-lg font-bold text-slate-900">Masuk Super Admin</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Akses pengelola platform multi-tenant.</p>
                </div>
                <button type="button" @click="mode = 'token'" class="text-xs text-slate-400 hover:text-slate-600">
                    Kembali
                </button>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username Master</label>
                    <input type="text" name="username" id="admin_username" value="superadmin" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                        placeholder="superadmin">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                    <input type="password" name="password" id="admin_password" value="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                        placeholder="••••••••">
                </div>

                <button type="submit" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-sm font-semibold shadow-md shadow-slate-900/25 transition flex items-center justify-center gap-2">
                    <span>Masuk sebagai Super Admin &rarr;</span>
                </button>
            </form>
        </div>

        <!-- Footer Info -->
        <div class="text-center text-xs text-slate-500">
            Platform Taqreer Multi-Tenant &copy; {{ date('Y') }}. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>
