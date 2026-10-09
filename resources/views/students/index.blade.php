@extends('layouts.app', [
    'header' => 'Master Data '.\App\Models\Institution::term('student'),
    'subheader' => 'Kelola basis data '.strtolower(\App\Models\Institution::term('student')).', nomor induk (NIS), dan penempatan '.strtolower(\App\Models\Institution::term('class'))
])

@section('content')
<div class="space-y-6">
    <!-- Top Action & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('students.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau NIS..."
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="classroom_id" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ request('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>

            @if(request('search') || request('classroom_id'))
                <a href="{{ route('students.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 w-full md:w-auto" x-data="{ showImport: false }">
            <a href="{{ route('students.export', request()->only(['classroom_id', 'search'])) }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Ekspor Excel</span>
            </a>

            <button type="button" @click="showImport = true" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-9l-4-4m0 0L8 7m4-4v12"/>
                </svg>
                <span>Impor Excel</span>
            </button>

            <a href="{{ route('students.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Santri Baru</span>
            </a>

            <!-- Import Modal -->
            <div x-show="showImport" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
                 @keydown.escape.window="showImport = false">
                <div @click.outside="showImport = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Impor Data Santri dari Excel</h3>
                        <button type="button" @click="showImport = false" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>

                    <p class="text-xs text-slate-500">
                        Unggah berkas Excel (.xlsx) atau CSV dengan kolom: <strong>NIS, Nama Lengkap, Kelas, Jenis Kelamin, Status Aktif</strong>.
                        Santri dengan NIS yang sudah ada akan diperbarui datanya; NIS baru akan ditambahkan sebagai santri baru.
                    </p>

                    <a href="{{ route('students.import.template') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Unduh Template Contoh
                    </a>

                    <form action="{{ route('students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                            class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="showImport = false" class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 transition">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">Impor Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">NIS</th>
                        <th class="px-6 py-3.5">Nama Lengkap</th>
                        <th class="px-6 py-3.5">Kelas</th>
                        <th class="px-6 py-3.5">L/P</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $st)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-medium text-slate-800 text-xs">
                                {{ $st->nis }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                <a href="{{ route('students.show', $st->id) }}" class="hover:text-emerald-600 transition flex items-center gap-1.5 group">
                                    <span>{{ $st->name }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-300 group-hover:text-emerald-500 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-slate-100 text-slate-700">
                                    Kelas {{ $st->classroom->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ $st->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($st->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('students.show', $st->id) }}" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Lihat Profil & Grafik Tren">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </a>

                                    <a href="{{ route('students.edit', $st->id) }}" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('students.destroy', $st->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus santri ini? Seluruh riwayat laporan santri ini akan ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data santri yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

