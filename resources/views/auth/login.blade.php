<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ $tenant?->name ?? 'Myra Reporting System' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md space-y-6">
        <!-- Logo & Header -->
        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center font-extrabold text-white text-2xl shadow-xl mb-4"
                 style="background-color: {{ $tenant?->accent_color ?: '#059669' }}; box-shadow: 0 10px 25px -5px {{ ($tenant?->accent_color ?: '#059669') }}66;">
                {{ substr($tenant?->name ?? 'Myra', 0, 1) }}
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">{{ $tenant?->name ?? 'Myra Reporting System' }}</h1>
            <p class="text-xs text-slate-400 mt-1">Platform Laporan Bulanan Santri & Evaluasi Berkala</p>

            @if($tenant)
                <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/90 border border-slate-700 text-xs text-slate-300">
                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $tenant->accent_color ?: '#059669' }}"></span>
                    <span>Token: <strong class="font-mono text-white">{{ $tenant->token }}</strong></span>
                    <form action="{{ route('gateway.reset') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-[11px] text-emerald-400 hover:text-emerald-300 underline font-semibold ml-1 cursor-pointer">
                            Ganti
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl p-8 shadow-2xl border border-slate-100/10">
            <!-- Flash Message -->
            @if(session('success'))
                <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pengguna (Username)</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" required autofocus
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                        placeholder="Masukkan username...">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700">Kata Sandi</label>
                    </div>
                    <input type="password" name="password" id="password" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded-sm text-emerald-600 focus:ring-emerald-500">
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-emerald-600/25 transition">
                    Masuk ke Sistem &rarr;
                </button>
            </form>

            <!-- Quick Demo Role Switcher -->
            {{-- Demo accounts are only offered on local development installs, never in production. --}}
            @if(app()->environment('local'))
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        Akun Uji Coba Cepat (1-Klik)
                    </p>
                    <form action="{{ route('gateway.reset') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-[10px] text-slate-500 hover:text-slate-800 font-medium">
                            Ganti Lembaga
                        </button>
                    </form>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                    <button type="button" onclick="setCreds('admin', 'password')" class="p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:text-emerald-800 border border-slate-200/80 text-left transition">
                        <span class="font-bold block text-slate-800">Admin</span>
                        <span class="text-[10px] text-slate-400 font-mono">admin</span>
                    </button>
                    <button type="button" onclick="setCreds('guru', 'password')" class="p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:text-emerald-800 border border-slate-200/80 text-left transition">
                        <span class="font-bold block text-slate-800">Guru Tahfidz</span>
                        <span class="text-[10px] text-slate-400 font-mono">guru</span>
                    </button>
                    <button type="button" onclick="setCreds('walikelas', 'password')" class="p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:text-emerald-800 border border-slate-200/80 text-left transition">
                        <span class="font-bold block text-slate-800">Wali Kelas</span>
                        <span class="text-[10px] text-slate-400 font-mono">walikelas</span>
                    </button>
                    <button type="button" onclick="setCreds('kesantrian', 'password')" class="p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:text-emerald-800 border border-slate-200/80 text-left transition">
                        <span class="font-bold block text-slate-800">Kesantrian</span>
                        <span class="text-[10px] text-slate-400 font-mono">kesantrian</span>
                    </button>
                    <button type="button" onclick="setCreds('tu', 'password')" class="p-2 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:text-emerald-800 border border-slate-200/80 text-left transition">
                        <span class="font-bold block text-slate-800">TU / Keuangan</span>
                        <span class="text-[10px] text-slate-400 font-mono">tu</span>
                    </button>
                    <button type="button" onclick="setCreds('walimurid1', 'password')" class="col-span-2 p-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-left transition">
                        <div class="flex items-center justify-between">
                            <span class="font-bold block">Wali Murid (Orang Tua Santri 1)</span>
                            <span class="text-[10px] bg-emerald-200/60 px-2 py-0.5 rounded-sm text-emerald-800 font-mono font-bold">walimurid1</span>
                        </div>
                        <span class="text-[10px] text-emerald-700 block mt-0.5">Tersedia akun <code class="font-mono font-bold">walimurid1</code> s/d <code class="font-mono font-bold">walimurid10</code> (Password: password)</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        function setCreds(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
