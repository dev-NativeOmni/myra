@extends('layouts.app', [
    'header' => 'Daftar '.\App\Models\Institution::term('class').' & Rombel',
    'subheader' => 'Kelola nama-nama '.strtolower(\App\Models\Institution::term('class')).' untuk pengelompokan data '.strtolower(\App\Models\Institution::term('student')).' dan pelaporan'
])

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
    <!-- Left Column: Form Tambah Kelas -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Tambah Kelas Baru</h2>
        <p class="text-xs text-slate-500 mb-5">Masukkan nama kelas/rombel yang aktif di lembaga.</p>

        <form action="{{ route('classrooms.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kelas <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: 7, 8A, Tahfidz 1">
            </div>

            <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                + Tambah Kelas
            </button>
        </form>
    </div>

    <!-- Right Column: Tabel Daftar Kelas -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Daftar Kelas Aktif</h3>
            <span class="text-xs text-slate-500">Total {{ $classrooms->count() }} Kelas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Nama Kelas</th>
                        <th class="px-6 py-3.5">Jumlah Santri</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($classrooms as $room)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                Kelas {{ $room->name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $room->students_count }} Santri
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit Inline Form / Trigger -->
                                    <button type="button" 
                                        onclick="const newName = prompt('Ubah nama kelas:', '{{ $room->name }}'); if(newName && newName !== '{{ $room->name }}') { document.getElementById('edit-form-{{ $room->id }}').name.value = newName; document.getElementById('edit-form-{{ $room->id }}').submit(); }"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Form -->
                                    <form action="{{ route('classrooms.destroy', $room->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kelas ini? Santri di dalam kelas ini juga akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <form id="edit-form-{{ $room->id }}" action="{{ route('classrooms.update', $room->id) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="name" value="{{ $room->name }}">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                Belum ada kelas yang ditambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

