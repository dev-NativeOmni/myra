@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    createModal: false,
    editModal: false,
    editData: {
        id: null,
        name: '',
        token: '',
        city: '',
        director_name: '',
        director_title: '',
        accent_color: '#059669'
    },
    openEdit(inst) {
        this.editData = { ...inst };
        this.editModal = true;
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold mb-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Multi-Tenant Master Control
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Lembaga (SaaS)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Kelola seluruh lembaga terdaftar, atur token akses, dan pantau statistik data tiap lembaga.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="createModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Lembaga Baru</span>
            </button>
        </div>
    </div>

    <!-- Active Tenant Banner -->
    @if($currentTenant)
        <div class="p-4 rounded-2xl bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg text-white"
                     style="background-color: {{ $currentTenant->accent_color ?: '#059669' }};">
                    {{ substr($currentTenant->name, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-emerald-400">Konteks Aktif Saat Ini:</span>
                        <span class="px-2 py-0.5 rounded-sm text-[10px] font-mono font-bold bg-slate-800 text-white border border-slate-700">{{ $currentTenant->token }}</span>
                    </div>
                    <div class="text-sm font-bold text-white">{{ $currentTenant->name }}</div>
                </div>
            </div>
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <span>Kota: <strong class="text-slate-200">{{ $currentTenant->city }}</strong></span>
                <span>•</span>
                <span>Pimpinan: <strong class="text-slate-200">{{ $currentTenant->director_name }}</strong></span>
            </div>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-medium text-slate-500">Total Lembaga</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $institutions->count() }}</div>
            <div class="text-[11px] text-emerald-600 mt-1 font-medium">{{ $institutions->where('is_active', true)->count() }} aktif</div>
        </div>
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-medium text-slate-500">Total Santri (Semua Lembaga)</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $institutions->sum('students_count') }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Terdaftar di sistem</div>
        </div>
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-medium text-slate-500">Total Kelas / Halaqah</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $institutions->sum('classrooms_count') }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Di seluruh lembaga</div>
        </div>
        <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs">
            <div class="text-xs font-medium text-slate-500">Total Laporan Bulanan</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $institutions->sum('monthly_reports_count') }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Terekam di database</div>
        </div>
    </div>

    <!-- Institutions Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Lembaga Terdaftar</h3>
            <span class="text-xs text-slate-500">{{ $institutions->count() }} lembaga ditemukan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">Lembaga & Token</th>
                        <th class="px-4 py-3">Lokasi / Kota</th>
                        <th class="px-4 py-3">Pimpinan</th>
                        <th class="px-4 py-3 text-center">Santri</th>
                        <th class="px-4 py-3 text-center">Kelas</th>
                        <th class="px-4 py-3 text-center">Pengguna</th>
                        <th class="px-4 py-3 text-center">Laporan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($institutions as $inst)
                        <tr class="hover:bg-slate-50/80 transition {{ $currentTenant && $currentTenant->id === $inst->id ? 'bg-emerald-50/40' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white shrink-0"
                                         style="background-color: {{ $inst->accent_color ?: '#059669' }};">
                                        {{ substr($inst->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate">{{ $inst->name }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded-sm bg-slate-100 text-slate-700 border border-slate-200">{{ $inst->token }}</span>
                                            @if($currentTenant && $currentTenant->id === $inst->id)
                                                <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-sm">Aktif Dipilih</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600 font-medium">{{ $inst->city }}</td>
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-800">{{ $inst->director_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $inst->director_title }}</div>
                            </td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ $inst->students_count }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ $inst->classrooms_count }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ $inst->users_count }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ $inst->monthly_reports_count }}</td>
                            <td class="px-4 py-4 text-center">
                                @if($inst->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(!$currentTenant || $currentTenant->id !== $inst->id)
                                        <form action="{{ route('platform.institutions.switch', $inst->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" title="Beralih ke konteks lembaga ini"
                                                    class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button" @click="openEdit(@js($inst))" title="Edit Lembaga"
                                            class="p-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <form action="{{ route('platform.institutions.toggle', $inst->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin mengubah status lembaga ini?')">
                                        @csrf
                                        <button type="submit" title="{{ $inst->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                class="p-1.5 rounded-lg {{ $inst->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }} transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data lembaga terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Institution Modal -->
    <div x-show="createModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200" @click.outside="createModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <h3 class="text-base font-bold text-slate-900">Tambah Lembaga Baru</h3>
                <button type="button" @click="createModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('platform.institutions.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lembaga / Pesantren</label>
                    <input type="text" name="name" required placeholder="Contoh: Pondok Pesantren Al-Hikmah"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Token Akses Lembaga (Opsional)</label>
                    <input type="text" name="token" placeholder="Kosongkan untuk dibuat otomatis (Contoh: ALHIKMAH-01)"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    <p class="text-[10px] text-slate-400 mt-1">Token ini digunakan santri, guru, dan wali murid untuk masuk.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kota / Domisili</label>
                        <input type="text" name="city" required placeholder="Contoh: Malang"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warna Aksen / Tema</label>
                        <input type="color" name="accent_color" value="#059669"
                               class="w-full h-9 p-1 rounded-xl border border-slate-300 cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pimpinan / Mudir</label>
                        <input type="text" name="director_name" required placeholder="Contoh: Ustadz Ahmad, Lc."
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan Pimpinan</label>
                        <input type="text" name="director_title" required placeholder="Contoh: Pengasuh Pesantren" value="Pengasuh Pesantren"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="createModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                        Simpan & Buat Lembaga
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Institution Modal -->
    <div x-show="editModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200" @click.outside="editModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <h3 class="text-base font-bold text-slate-900">Edit Data Lembaga</h3>
                <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="'/platform/institutions/' + editData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lembaga</label>
                    <input type="text" name="name" x-model="editData.name" required
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Token Akses</label>
                    <input type="text" name="token" x-model="editData.token" required
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kota / Domisili</label>
                        <input type="text" name="city" x-model="editData.city" required
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warna Aksen</label>
                        <input type="color" name="accent_color" x-model="editData.accent_color"
                               class="w-full h-9 p-1 rounded-xl border border-slate-300 cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pimpinan</label>
                        <input type="text" name="director_name" x-model="editData.director_name" required
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan Pimpinan</label>
                        <input type="text" name="director_title" x-model="editData.director_title" required
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    <button type="button" @click="editModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
