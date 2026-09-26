@extends('layouts.app', [
    'header' => 'Rincian Laporan Bulanan',
    'subheader' => $report->student->name . ' (NIS: ' . $report->student->nis . ') - Periode: ' . $report->period_title
])

@section('content')
<div class="space-y-6 max-w-5xl">
    <!-- Header Meta & Actions -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    Kelas {{ $report->student->classroom->name ?? '-' }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $report->status == 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ ucfirst($report->status) }}
                </span>
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ $report->student->name }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Tanggal Terbit: {{ \Carbon\Carbon::parse($report->report_date)->translatedFormat('d F Y') }} &bull;
                Cut-off Keuangan: {{ \Carbon\Carbon::parse($report->cutoff_date)->translatedFormat('d F Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <a href="{{ route('reports.edit-record', $report->id) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Edit Capaian Nilai</span>
            </a>

            <a href="{{ route('reports.preview', $report->id) }}" target="_blank" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span>Cetak / Pratinjau PDF</span>
            </a>
        </div>
    </div>

    @php 
        $rec = $report->record;
        $tahfidzFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_TAHFIDZ);
        $kesantrianFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_KESANTRIAN);
        $academicFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_AKADEMIK);
        $adminFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_ADMINISTRASI);
    @endphp

    <!-- 4 Modules Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Modul A: Tahfidz -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    A. Modul Tahfidz
                </h3>
            </div>

            <div class="space-y-2.5 text-xs text-slate-700">
                @forelse($tahfidzFields as $index => $fld)
                    @if($fld->type === 'textarea')
                        <div class="pt-2">
                            <span class="text-slate-500 block mb-1 font-semibold">{{ $index + 1 }}. {{ $fld->label }}:</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-xl leading-relaxed italic">
                                {{ $rec ? ($rec->getFieldValue($fld->key) ?: 'Belum ada catatan.') : '-' }}
                            </p>
                        </div>
                    @else
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">{{ $index + 1 }}. {{ $fld->label }}</span>
                            <span class="font-semibold">{{ $rec ? ($rec->getFieldValue($fld->key) ?? '-') : '-' }}</span>
                        </div>
                    @endif
                @empty
                    <p class="text-slate-400 italic">Belum ada item penilaian tahfidz.</p>
                @endforelse
            </div>
        </div>

        <!-- Modul B: Kesantrian -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    B. Modul Kesantrian
                </h3>
            </div>

            <div class="space-y-2.5 text-xs text-slate-700">
                @forelse($kesantrianFields as $index => $fld)
                    @if($fld->type === 'textarea')
                        <div class="pt-2">
                            <span class="text-slate-500 block mb-1 font-semibold">{{ $index + 1 }}. {{ $fld->label }}:</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-xl leading-relaxed italic">
                                {{ $rec ? ($rec->getFieldValue($fld->key) ?: 'Belum ada catatan.') : '-' }}
                            </p>
                        </div>
                    @else
                        @php
                            $val = $rec ? $rec->getFieldValue($fld->key) : null;
                            if ($fld->key === 'body_height_cm' && $val) {
                                $val .= ' cm';
                            } elseif ($fld->key === 'body_weight_kg' && $val) {
                                $val .= ' kg';
                            }
                        @endphp
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">{{ $index + 1 }}. {{ $fld->label }}</span>
                            <span class="font-semibold">{{ $val ?? '-' }}</span>
                        </div>
                    @endif
                @empty
                    <p class="text-slate-400 italic">Belum ada item penilaian kesantrian.</p>
                @endforelse
            </div>
        </div>

        <!-- Modul C: Akademik -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    C. Modul Akademik / Wali Kelas
                </h3>
            </div>

            <div class="space-y-2.5 text-xs text-slate-700">
                @forelse($academicFields as $index => $fld)
                    @if($fld->type === 'textarea')
                        <div>
                            <span class="text-slate-500 block mb-1 font-semibold">{{ $index + 1 }}. {{ $fld->label }}:</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-xl leading-relaxed italic">
                                {{ $rec ? ($rec->getFieldValue($fld->key) ?: 'Belum ada catatan.') : '-' }}
                            </p>
                        </div>
                    @else
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">{{ $index + 1 }}. {{ $fld->label }}</span>
                            <span class="font-semibold">{{ $rec ? ($rec->getFieldValue($fld->key) ?? '-') : '-' }}</span>
                        </div>
                    @endif
                @empty
                    <p class="text-slate-400 italic">Belum ada item penilaian akademik.</p>
                @endforelse
            </div>
        </div>

        <!-- Modul D: Administrasi -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                    D. Modul Administrasi & Keuangan
                </h3>
            </div>

            <div class="space-y-2.5 text-xs text-slate-700">
                @forelse($adminFields as $index => $fld)
                    @if($fld->type === 'textarea')
                        <div>
                            <span class="text-slate-500 block mb-1 font-semibold">{{ $index + 1 }}. {{ $fld->label }}:</span>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-xl leading-relaxed italic">
                                {{ $rec ? ($rec->getFieldValue($fld->key) ?: 'Belum ada catatan.') : '-' }}
                            </p>
                        </div>
                    @else
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">{{ $index + 1 }}. {{ $fld->label }}</span>
                            <span class="font-semibold">{{ $rec ? ($rec->getFieldValue($fld->key) ?? '-') : '-' }}</span>
                        </div>
                    @endif
                @empty
                    <p class="text-slate-400 italic">Belum ada item penilaian administrasi.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

