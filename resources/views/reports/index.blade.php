@extends('layouts.app', [
    'header' => 'Rekapitulasi Laporan Bulanan '.\App\Models\Institution::term('student'),
    'subheader' => 'Monitoring kelengkapan data 4 modul evaluasi dan cetak laporan per '.strtolower(\App\Models\Institution::term('student'))
])

@section('content')
<div class="space-y-6">
    <!-- Top Action & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 sm:w-60">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari santri atau NIS..."
                    class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select name="period_title" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period }}" {{ request('period_title') == $period ? 'selected' : '' }}>
                        {{ $period }}
                    </option>
                @endforeach
            </select>

            <select name="classroom_id" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ request('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>

            @if(request('search') || request('period_title') || request('classroom_id'))
                <a href="{{ route('reports.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 w-full md:w-auto">
            <a href="{{ route('reports.completeness') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Cek Kelengkapan</span>
            </a>

            <a href="{{ route('reports.batch-export') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Ekspor Massal PDF</span>
            </a>

            <a href="{{ route('reports.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>+ Buat Laporan</span>
            </a>
        </div>
    </div>

    <!-- Quick Navigation Modules -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('modules.tahfidz') }}" class="p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-100/70 transition flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-bold text-emerald-900">Input Tahfidz &rarr;</span>
        </a>
        <a href="{{ route('modules.kesantrian') }}" class="p-3.5 rounded-xl border border-blue-200 bg-blue-50/50 hover:bg-blue-100/70 transition flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <span class="text-xs font-bold text-blue-900">Input Kesantrian &rarr;</span>
        </a>
        <a href="{{ route('modules.akademik') }}" class="p-3.5 rounded-xl border border-amber-200 bg-amber-50/50 hover:bg-amber-100/70 transition flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <span class="text-xs font-bold text-amber-900">Input Akademik &rarr;</span>
        </a>
        <a href="{{ route('modules.administrasi') }}" class="p-3.5 rounded-xl border border-purple-200 bg-purple-50/50 hover:bg-purple-100/70 transition flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
            <span class="text-xs font-bold text-purple-900">Input Administrasi &rarr;</span>
        </a>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Santri</th>
                        <th class="px-6 py-3.5">Periode & Kelas</th>
                        <th class="px-6 py-3.5">Kelengkapan 4 Modul</th>
                        <th class="px-6 py-3.5">Tanggal Terbit</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $rep)
                        @php
                            $rec = $rep->record;
                            $hasTahfidz = !empty($rec?->tahfidz_setoran) || !empty($rec?->tahfidz_notes);
                            $hasKesantrian = !empty($rec?->adab_ibadah) || !empty($rec?->kesantrian_notes);
                            $hasAkademik = !empty($rec?->academic_notes);
                            $hasAdmin = !empty($rec?->last_spp) || !empty($rec?->registration_status);
                            $previewUrl = route('reports.preview', $rep->id);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('reports.show', $rep->id) }}" class="font-semibold text-slate-900 hover:text-emerald-600">
                                    {{ $rep->student->name }}
                                </a>
                                <div class="text-xs text-slate-400 font-mono">{{ $rep->student->nis }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-emerald-800 text-xs">{{ $rep->period_title }}</div>
                                <div class="text-xs text-slate-500">Kelas {{ $rep->student->classroom->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-bold {{ $hasTahfidz ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-400' }}" title="Tahfidz">
                                        Tahfidz
                                    </span>
                                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-bold {{ $hasKesantrian ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400' }}" title="Kesantrian">
                                        Kesantrian
                                    </span>
                                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-bold {{ $hasAkademik ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-400' }}" title="Akademik">
                                        Akademik
                                    </span>
                                    <span class="px-2 py-0.5 rounded-sm text-[10px] font-bold {{ $hasAdmin ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-400' }}" title="Administrasi">
                                        Admin
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ \Carbon\Carbon::parse($rep->report_date)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $rep->status == 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ ucfirst($rep->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('reports.edit-record', $rep->id) }}" class="p-1.5 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition" title="Input / Edit Nilai">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <!-- Live Preview Modal Trigger (DESIGN 6.3) -->
                                    <button type="button" 
                                        onclick="openPdfModal('{{ $previewUrl }}', '{{ $rep->student->name }}')" 
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition" title="Live Preview PDF">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Pratinjau
                                    </button>

                                    <form action="{{ route('reports.destroy', $rep->id) }}" method="POST" onsubmit="return confirm('Hapus laporan bulanan ini?');">
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
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                Belum ada data laporan bulanan. Klik "Buat Laporan" untuk memulai siklus periode baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Live PDF Preview Modal (DESIGN 6.3) -->
<div id="pdf-modal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4 lg:p-6">
    <div class="bg-white w-full max-w-5xl h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200">
        <!-- Modal Header -->
        <div class="px-6 py-3.5 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="font-bold text-sm" id="modal-title">Pratinjau PDF Laporan Bulanan</span>
            </div>
            <div class="flex items-center gap-2">
                <a id="modal-open-new-tab" href="#" target="_blank" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition">
                    Buka Tab Baru &nearr;
                </a>
                <button type="button" onclick="closePdfModal()" class="p-1 text-slate-400 hover:text-white rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Iframe PDF Viewer Container -->
        <div class="flex-1 bg-slate-100 p-1 relative">
            <iframe id="modal-iframe" src="" class="w-full h-full rounded-b-xl border-0" frameborder="0"></iframe>
        </div>
    </div>
</div>

<script>
    function openPdfModal(url, studentName) {
        document.getElementById('modal-title').innerText = 'Pratinjau PDF - ' + studentName;
        document.getElementById('modal-open-new-tab').href = url;
        document.getElementById('modal-iframe').src = url;
        document.getElementById('pdf-modal').classList.remove('hidden');
    }

    function closePdfModal() {
        document.getElementById('pdf-modal').classList.add('hidden');
        document.getElementById('modal-iframe').src = '';
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePdfModal();
        }
    });
</script>
@endsection
