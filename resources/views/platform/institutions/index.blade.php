@extends('layouts.app', [
    'header' => 'Platform Taqreer',
    'subheader' => 'Pantau seluruh lembaga dan bantu admin lembaga bila ada kendala',
])

@php
    $inputClass = 'w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden';
    $labelClass = 'block text-xs font-semibold text-slate-700 mb-1';
@endphp

@section('content')
<div class="space-y-6" x-data="{
    createModal: {{ $errors->hasAny(['name', 'token', 'city', 'director_name', 'director_title', 'admin_name', 'admin_username', 'admin_password']) ? 'true' : 'false' }},
    editModal: false,
    editData: { id: null, name: '', token: '', city: '', director_name: '', director_title: '', accent_color: '#059669' },
    openEdit(inst) { this.editData = { ...inst }; this.editModal = true; }
}">
    <!-- Platform Metrics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        @foreach([
            ['Lembaga', $institutions->count(), $institutions->where('is_active', true)->count().' aktif'],
            ['Santri', $institutions->sum('students_count'), 'di semua lembaga'],
            ['Pengguna', $institutions->sum('users_count'), 'staf & wali murid'],
            ['Laporan Bulanan', $institutions->sum('monthly_reports_count'), 'tersimpan'],
        ] as [$label, $value, $note])
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
                <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($value) }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ $note }}</div>
            </div>
        @endforeach
    </div>

    <!-- Institutions -->
    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">Lembaga Terdaftar</h2>
            <button type="button" @click="createModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Lembaga</span>
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($institutions as $inst)
                <article class="min-w-0 bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5 space-y-4 {{ $inst->is_active ? '' : 'opacity-75' }}">
                    <header class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white shrink-0" style="background-color: {{ $inst->accent_color ?: '#059669' }};">
                            {{ mb_substr($inst->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold text-slate-900 leading-snug">{{ $inst->name }}</h3>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px]">
                                <span class="font-mono font-bold px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-700 border border-slate-200">{{ $inst->token }}</span>
                                <span class="text-slate-400">{{ $inst->city }}</span>
                                <span class="px-2 py-0.5 rounded-full font-semibold border {{ $inst->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                    {{ $inst->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>
                    </header>

                    <dl class="grid grid-cols-4 gap-2 text-center">
                        @foreach(['Santri' => $inst->students_count, 'Kelas' => $inst->classrooms_count, 'Pengguna' => $inst->users_count, 'Laporan' => $inst->monthly_reports_count] as $label => $count)
                            <div class="rounded-xl bg-slate-50 py-2">
                                <dd class="text-base font-bold text-slate-900">{{ number_format($count) }}</dd>
                                <dt class="text-[10px] text-slate-500">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>

                    <p class="text-[11px] text-slate-500">
                        Aktivitas laporan terakhir:
                        <strong class="text-slate-700">
                            {{ $inst->monthly_reports_max_updated_at ? \Carbon\Carbon::parse($inst->monthly_reports_max_updated_at)->diffForHumans() : 'belum ada' }}
                        </strong>
                    </p>

                    <div class="space-y-2">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Bantu sebagai Admin</div>
                        @forelse($inst->admins as $admin)
                            <form method="POST" action="{{ route('platform.institutions.impersonate', [$inst, $admin->id]) }}"
                                  onsubmit="return confirm('Masuk ke {{ addslashes($inst->name) }} sebagai {{ addslashes($admin->name) }}? Sesi ini akan dicatat.')">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 text-left transition">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-slate-800 truncate">{{ $admin->name }}</span>
                                        <span class="block text-[11px] font-mono text-slate-400">{{ '@'.$admin->username }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-emerald-700">Masuk &rarr;</span>
                                </button>
                            </form>
                        @empty
                            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">Lembaga ini belum memiliki akun Admin.</p>
                        @endforelse
                    </div>

                    <footer class="flex gap-2 pt-1">
                        <button type="button" @click="openEdit(@js($inst->only(['id', 'name', 'token', 'city', 'director_name', 'director_title', 'accent_color'])))"
                                class="flex-1 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                            Edit Data Lembaga
                        </button>
                        <form method="POST" action="{{ route('platform.institutions.toggle', $inst) }}" class="flex-1"
                              onsubmit="return confirm('{{ $inst->is_active ? 'Nonaktifkan lembaga ini? Penggunanya tidak bisa masuk lewat token lembaga.' : 'Aktifkan kembali lembaga ini?' }}')">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 rounded-xl text-xs font-semibold border transition {{ $inst->is_active ? 'bg-white border-amber-200 text-amber-700 hover:bg-amber-50' : 'bg-white border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                {{ $inst->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </footer>
                </article>
            @empty
                <p class="text-sm text-slate-400 bg-white rounded-2xl border border-slate-200 p-8 text-center lg:col-span-2">Belum ada lembaga terdaftar.</p>
            @endforelse
        </div>
    </section>

    <!-- Support Session Log -->
    <section class="bg-white rounded-2xl border border-slate-200 shadow-xs p-4 sm:p-5">
        <h2 class="text-sm font-bold text-slate-900">Riwayat Sesi Bantuan</h2>
        <p class="text-xs text-slate-500 mt-0.5 mb-3">Setiap kali Super Admin masuk sebagai admin lembaga tercatat di sini.</p>
        <ul class="divide-y divide-slate-100">
            @forelse($supportSessions as $session)
                <li class="py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs">
                    <span class="text-slate-700">
                        <strong>{{ $session->institution?->name ?? 'Lembaga terhapus' }}</strong>
                        sebagai {{ $session->impersonatedUser?->name ?? 'akun terhapus' }}
                    </span>
                    <span class="text-slate-400">
                        {{ $session->started_at->translatedFormat('d M Y H:i') }}
                        &ndash;
                        {{ $session->ended_at?->translatedFormat('H:i') ?? 'masih berlangsung' }}
                    </span>
                </li>
            @empty
                <li class="py-3 text-xs text-slate-400">Belum ada sesi bantuan.</li>
            @endforelse
        </ul>
    </section>

    <!-- Create Institution Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-slate-900/60" @keydown.escape.window="createModal = false">
        <div class="bg-white w-full sm:max-w-lg max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl p-5 sm:p-7 shadow-2xl" @click.outside="createModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-900">Tambah Lembaga Baru</h3>
                <button type="button" @click="createModal = false" class="p-1 text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
            </div>

            <form action="{{ route('platform.institutions.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="{{ $labelClass }}">Nama Lembaga / Pesantren</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Pondok Pesantren Al-Hikmah" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Token Akses Lembaga (opsional)</label>
                    <input type="text" name="token" value="{{ old('token') }}" placeholder="Kosongkan untuk dibuat otomatis" class="{{ $inputClass }} font-mono uppercase">
                    <p class="text-[11px] text-slate-400 mt-1">Dipakai guru dan wali murid untuk memilih lembaga saat masuk.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Kota</label>
                        <input type="text" name="city" value="{{ old('city') }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Warna Tema</label>
                        <input type="color" name="accent_color" value="{{ old('accent_color', '#059669') }}" class="w-full h-10 p-1 rounded-xl border border-slate-300 cursor-pointer">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Nama Pimpinan</label>
                        <input type="text" name="director_name" value="{{ old('director_name') }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jabatan Pimpinan</label>
                        <input type="text" name="director_title" value="{{ old('director_title', 'Pengasuh Pesantren') }}" required class="{{ $inputClass }}">
                    </div>
                </div>

                <fieldset class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <legend class="px-1 text-xs font-bold text-slate-800">Akun Admin Lembaga</legend>
                    <p class="text-[11px] text-slate-500">Admin inilah yang mengelola data lembaga. Berikan username dan password ini kepada pengelola lembaga.</p>
                    <div>
                        <label class="{{ $labelClass }}">Nama Admin</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required class="{{ $inputClass }}">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $labelClass }}">Username</label>
                            <input type="text" name="admin_username" value="{{ old('admin_username') }}" required class="{{ $inputClass }} font-mono">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Password (min. 8 karakter)</label>
                            <input type="password" name="admin_password" required minlength="8" autocomplete="new-password" class="{{ $inputClass }}">
                        </div>
                    </div>
                </fieldset>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">Buat Lembaga & Admin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Institution Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-slate-900/60" @keydown.escape.window="editModal = false">
        <div class="bg-white w-full sm:max-w-lg max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl p-5 sm:p-7 shadow-2xl" @click.outside="editModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-900">Edit Data Lembaga</h3>
                <button type="button" @click="editModal = false" class="p-1 text-slate-400 hover:text-slate-600" aria-label="Tutup">&times;</button>
            </div>

            <form :action="'{{ url('platform/institutions') }}/' + editData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="{{ $labelClass }}">Nama Lembaga</label>
                    <input type="text" name="name" x-model="editData.name" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Token Akses</label>
                    <input type="text" name="token" x-model="editData.token" required class="{{ $inputClass }} font-mono uppercase">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Kota</label>
                        <input type="text" name="city" x-model="editData.city" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Warna Tema</label>
                        <input type="color" name="accent_color" x-model="editData.accent_color" class="w-full h-10 p-1 rounded-xl border border-slate-300 cursor-pointer">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Nama Pimpinan</label>
                        <input type="text" name="director_name" x-model="editData.director_name" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Jabatan Pimpinan</label>
                        <input type="text" name="director_title" x-model="editData.director_title" required class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">Batal</button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
