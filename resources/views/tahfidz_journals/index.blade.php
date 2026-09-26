@extends('layouts.app', [
    'header' => 'Jurnal Harian Tahfidz Al-Qur\'an',
    'subheader' => 'Pencatatan setoran harian halaqah santri (Ziyadah, Muraja\'ah, Tilawah, & Nilai Kelancaran)'
])

@section('content')
<div class="space-y-6" x-data="{
    selectedIds: [],
    allIds: @json($journals->pluck('id')),
    toggleSelectAll() {
        if (this.selectedIds.length === this.allIds.length) {
            this.selectedIds = [];
        } else {
            this.selectedIds = [...this.allIds];
        }
    },
    confirmBulkDelete() {
        if (this.selectedIds.length === 0) return;
        if (confirm(`Apakah Anda yakin ingin menghapus ${this.selectedIds.length} catatan setoran tahfidz yang dipilih secara massal? Tindakan ini tidak dapat dibatalkan.`)) {
            this.$nextTick(() => {
                document.getElementById('bulk-delete-form').submit();
            });
        }
    }
}">
    <!-- Top Mode Switcher Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('tahfidz-journals.spreadsheet') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold border border-slate-200 transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Mode Spreadsheet (Input Cepat)</span>
            </a>

            <a href="{{ route('tahfidz-journals.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Riwayat & Daftar Catatan</span>
            </a>
        </div>
    </div>

    <!-- Top Action & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('tahfidz-journals.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()"
                class="px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white">

            <select name="type" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Jenis Setoran</option>
                <option value="ziyadah" {{ request('type') == 'ziyadah' ? 'selected' : '' }}>Ziyadah (Hafalan Baru)</option>
                <option value="murajaah" {{ request('type') == 'murajaah' ? 'selected' : '' }}>Muraja'ah (Ulang)</option>
                <option value="tahsin" {{ request('type') == 'tahsin' ? 'selected' : '' }}>Tahsin</option>
                <option value="tilawah" {{ request('type') == 'tilawah' ? 'selected' : '' }}>Tilawah Mandiri</option>
                <option value="tasmi" {{ request('type') == 'tasmi' ? 'selected' : '' }}>Tasmi' Ujian</option>
            </select>

            <select name="classroom_id" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ request('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>

            @if(request('date') || request('type') || request('classroom_id') || request('student_id'))
                <a href="{{ route('tahfidz-journals.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="{{ route('tahfidz-journals.spreadsheet') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Mode Spreadsheet</span>
            </a>
        </div>
    </div>

    <!-- BULK ACTION BAR (Muncul jika ada baris yang dipilih) -->
    <div x-show="selectedIds.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         class="bg-slate-900 text-white p-3.5 sm:p-4 rounded-2xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3 border border-slate-800"
         style="display: none;">
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs sm:text-sm font-bold">
                <span x-text="selectedIds.length" class="text-emerald-400 text-base font-black"></span> catatan setoran dipilih
            </span>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <button type="button" @click="selectedIds = []"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer">
                Batal Pilih
            </button>

            <button type="button" @click="confirmBulkDelete()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white rounded-xl text-xs font-bold transition shadow-md shadow-rose-600/30 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span>Hapus Terpilih (<span x-text="selectedIds.length"></span>)</span>
            </button>
        </div>
    </div>

    <!-- Hidden Bulk Delete Form -->
    <form id="bulk-delete-form" method="POST" action="{{ route('tahfidz-journals.bulk-destroy') }}" style="display: none;">
        @csrf
        <template x-for="id in selectedIds" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <!-- Journals Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Riwayat Catatan Halaqah</h3>
            <span class="text-xs text-slate-500">Total {{ $journals->total() }} Catatan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3.5 w-12 text-center">
                            <input type="checkbox"
                                   @change="toggleSelectAll()"
                                   :checked="selectedIds.length > 0 && selectedIds.length === allIds.length"
                                   class="w-4 h-4 text-emerald-600 rounded-sm border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                   title="Pilih Semua di Halaman Ini">
                        </th>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-5 py-3.5">Santri</th>
                        <th class="px-5 py-3.5">Jenis Setoran</th>
                        <th class="px-5 py-3.5">Capaian (Juz / Surat / Halaman)</th>
                        <th class="px-5 py-3.5">Predikat</th>
                        <th class="px-5 py-3.5">Catatan Tajwid/Kelancaran</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($journals as $j)
                        @php
                            $typeBadge = match($j->type) {
                                'ziyadah' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'murajaah' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'tahsin' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'tasmi' => 'bg-purple-100 text-purple-800 border-purple-200',
                                default => 'bg-slate-100 text-slate-800 border-slate-200'
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition text-xs" :class="selectedIds.includes({{ $j->id }}) ? 'bg-emerald-50/40' : ''">
                            <!-- Checkbox Column -->
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox"
                                       value="{{ $j->id }}"
                                       x-model.number="selectedIds"
                                       class="w-4 h-4 text-emerald-600 rounded-sm border-slate-300 focus:ring-emerald-500 cursor-pointer">
                            </td>

                            <td class="px-5 py-4 font-mono font-medium text-slate-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($j->date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $j->student->name }}</div>
                                <div class="text-[11px] text-slate-400">NIS: {{ $j->student->nis }} &bull; Kelas {{ $j->student->classroom->name ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[11px] font-semibold border {{ $typeBadge }}">
                                    {{ $j->type_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                @if($j->surah)
                                    <span class="font-semibold text-slate-800 block">Surat {{ $j->surah }}</span>
                                @endif
                                <div class="text-[11px] text-slate-500">
                                    @if($j->juz) Juz {{ $j->juz }} &bull; @endif
                                    @if($j->ayah_start && $j->ayah_end) Ayat {{ $j->ayah_start }}-{{ $j->ayah_end }} &bull; @endif
                                    @if($j->page_count) {{ $j->page_count }} Halaman @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-emerald-700">
                                {{ $j->grade ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-600 max-w-xs truncate italic">
                                {{ $j->notes ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('tahfidz-journals.edit', $j->id) }}" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('tahfidz-journals.destroy', $j->id) }}" method="POST" onsubmit="return confirm('Hapus catatan setoran ini?');">
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
                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                Belum ada catatan setoran halaqah. Gunakan tombol "Mode Spreadsheet" untuk mulai mencatat jurnal tahfidz.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($journals->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $journals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

