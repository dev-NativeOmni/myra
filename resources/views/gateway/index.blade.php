<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerbang Akses Lembaga - Taqreer Multi-Tenant</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 sm:p-6 bg-slate-950 text-slate-100">
    <div class="w-full max-w-lg space-y-6">
        <!-- Logo & Header -->
        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl bg-emerald-600 mx-auto flex items-center justify-center font-extrabold text-white text-2xl shadow-xl shadow-emerald-500/25 mb-4">
                T
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Taqreer Multi-Tenant</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Sistem Pelaporan & Evaluasi Santri Berbasis Lembaga Mandiri</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white text-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-100/10 space-y-6">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Akses Khusus Lembaga
                </div>
                <h2 class="text-lg font-bold text-slate-900">Masukkan Token / Kode Lembaga</h2>
                <p class="text-xs text-slate-500 mt-0.5">Setiap lembaga memiliki kode unik tersendiri untuk mengamankan data dan konfigurasi modul.</p>
            </div>

            <!-- Flash Message -->
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Token / Kode Akses Lembaga</label>
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
                    <p class="text-[11px] text-slate-400 mt-1">Format token tidak sensitif terhadap huruf besar/kecil.</p>
                </div>

                <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-emerald-600/25 transition flex items-center justify-center gap-2">
                    <span>Buka Portal Lembaga</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            @if($sampleInstitutions->isNotEmpty())
                <div class="pt-5 border-t border-slate-100">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2.5">
                        Pilih Lembaga Terdaftar (Uji Coba Cepat)
                    </p>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($sampleInstitutions as $inst)
                            <a href="{{ route('gateway.direct', $inst->token) }}"
                               class="group flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200/80 hover:border-emerald-300 transition">
                                <div class="min-w-0 pr-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $inst->accent_color ?: '#059669' }}"></span>
                                        <span class="text-xs font-bold text-slate-800 group-hover:text-emerald-900 truncate">{{ $inst->name }}</span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">{{ $inst->city }}</span>
                                </div>
                                <div class="shrink-0 flex items-center gap-1.5 bg-white group-hover:bg-emerald-100/60 px-2.5 py-1 rounded-lg border border-slate-200 group-hover:border-emerald-200">
                                    <span class="text-[11px] font-mono font-bold text-slate-700 group-hover:text-emerald-800">{{ $inst->token }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer Info -->
        <div class="text-center text-xs text-slate-500">
            Platform Taqreer Multi-Tenant &copy; {{ date('Y') }}. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>
