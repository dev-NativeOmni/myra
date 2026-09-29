@extends('layouts.app', [
    'header' => 'Manajemen Pengguna & Hak Akses',
    'subheader' => 'Kelola akun pengguna, penugasan peran guru, dan tautan akun wali murid'
])

@section('content')
<div class="space-y-6">
    <!-- Top Action & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Search & Filter Form -->
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau email..."
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="role" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Peran (Role)</option>
                <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="guru" {{ request('role') == 'guru' ? 'selected' : '' }}>Guru Tahfidz</option>
                <option value="wali_kelas" {{ request('role') == 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                <option value="kesantrian" {{ request('role') == 'kesantrian' ? 'selected' : '' }}>Kesantrian</option>
                <option value="tu" {{ request('role') == 'tu' ? 'selected' : '' }}>Tata Usaha (TU)</option>
                <option value="wali_murid" {{ request('role') == 'wali_murid' ? 'selected' : '' }}>Wali Murid</option>
            </select>

            @if(request('search') || request('role'))
                <a href="{{ route('users.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>

        <div class="flex items-center gap-2.5 w-full md:w-auto" x-data="{ showImport: false }">
            <a href="{{ route('users.export', request()->only(['role', 'search'])) }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-semibold shadow-xs transition">
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

            <a href="{{ route('users.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>+ Tambah Pengguna Baru</span>
            </a>

            <!-- Import Modal -->
            <div x-show="showImport" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
                 @keydown.escape.window="showImport = false">
                <div @click.outside="showImport = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Impor Pengguna & Wali Murid dari Excel</h3>
                        <button type="button" @click="showImport = false" class="text-slate-400 hover:text-slate-700">&times;</button>
                    </div>

                    <p class="text-xs text-slate-500">
                        Kolom: <strong>Nama Lengkap, Username, Email, Peran, Kelas, NIS Santri, Password</strong>.
                        Username yang sudah ada akan diperbarui; username baru dibuat sebagai akun baru.
                    </p>

                    <ul class="text-xs text-slate-500 space-y-1 list-disc pl-4">
                        <li><strong>Peran</strong> diisi kode: <code class="text-slate-700">admin, guru, wali_kelas, kesantrian, tu, wali_murid</code>@if(auth()->user()->isSuperAdmin())<code class="text-slate-700">, super_admin</code>@endif.</li>
                        <li><strong>Kelas</strong> untuk guru, wali_kelas, dan kesantrian; beberapa kelas dipisah koma, contoh <code class="text-slate-700">1, 2</code>.</li>
                        <li><strong>NIS Santri</strong> wajib untuk wali_murid. Wali dengan beberapa anak cukup satu baris; NIS dipisah koma, contoh <code class="text-slate-700">2026001, 2026002</code>.</li>
                        <li><strong>Password</strong> wajib untuk akun baru (min. 8 karakter). Kosongkan untuk akun lama agar password tidak berubah.</li>
                        <li>Hapus file Excel berisi password setelah impor selesai.</li>
                    </ul>

                    <a href="{{ route('users.import.template') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Unduh Template Contoh
                    </a>

                    <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
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

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Nama Pengguna</th>
                        <th class="px-6 py-3.5">Username</th>
                        <th class="px-6 py-3.5">Email</th>
                        <th class="px-6 py-3.5">Peran (Role)</th>
                        <th class="px-6 py-3.5">Keterangan / Tautan Santri</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        @php
                            $badgeColor = match($u->role) {
                                'super_admin' => 'bg-purple-100 text-purple-800 border-purple-200',
                                'admin' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'guru' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'wali_kelas' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'kesantrian' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                'tu' => 'bg-rose-100 text-rose-800 border-rose-200',
                                'wali_murid' => 'bg-teal-100 text-teal-800 border-teal-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200'
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $u->name }}
                                @if($u->id === auth()->id())
                                    <span class="ml-1 text-[10px] text-emerald-600 font-bold">(Anda)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-mono font-bold text-slate-700">
                                @ {{ $u->username ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-500">
                                {{ $u->email ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeColor }}">
                                    {{ $u->role_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if($u->role === 'wali_murid' && $u->children->isNotEmpty())
                                    <span class="text-slate-400 block text-[11px]">Orang tua dari:</span>
                                    @foreach($u->children as $child)
                                        <span class="text-slate-800 font-medium block">{{ $child->name }}</span>
                                        <span class="text-slate-400 block font-mono text-[11px]">NIS: {{ $child->nis }} &bull; Kelas {{ $child->classroom->name ?? '-' }}</span>
                                    @endforeach
                                @elseif($u->role === 'wali_murid')
                                    <span class="text-amber-600 italic">Belum ditautkan ke santri</span>
                                @elseif(in_array($u->role, \App\Models\User::CLASSROOM_SCOPED_ROLES, true))
                                    @if($u->classrooms->isNotEmpty())
                                        <span class="text-slate-800 font-medium">Kelas: {{ $u->classrooms->pluck('name')->map(fn($n) => "Kelas {$n}")->join(', ') }}</span>
                                    @else
                                        <span class="text-amber-600 italic">Belum ditugaskan ke kelas manapun</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('users.edit', $u->id) }}" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus akun pengguna ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data pengguna yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

