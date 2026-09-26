@extends('layouts.app', [
    'header' => 'Status Kelengkapan Laporan',
    'subheader' => 'Pantau '.strtolower(\App\Models\Institution::term('student')).' mana yang belum diisi datanya di tiap modul sebelum laporan dicetak'
])

@section('content')
@php
    $moduleColors = [
        'tahfidz' => ['bg' => 'bg-emerald-50/90', 'border' => 'border-emerald-200', 'text' => 'text-emerald-900', 'dot' => 'bg-emerald-500', 'bar' => 'bg-emerald-500'],
        'kesantrian' => ['bg' => 'bg-blue-50/90', 'border' => 'border-blue-200', 'text' => 'text-blue-900', 'dot' => 'bg-blue-500', 'bar' => 'bg-blue-500'],
        'akademik' => ['bg' => 'bg-amber-50/90', 'border' => 'border-amber-200', 'text' => 'text-amber-900', 'dot' => 'bg-amber-500', 'bar' => 'bg-amber-500'],
        'administrasi' => ['bg' => 'bg-purple-50/90', 'border' => 'border-purple-200', 'text' => 'text-purple-900', 'dot' => 'bg-purple-500', 'bar' => 'bg-purple-500'],
    ];
@endphp

<div class="space-y-5">
    <!-- FILTER BAR -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
        <form method="GET" action="{{ route('reports.completeness') }}" class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Periode Rapor</label>
                <select name="period_title" onchange="this.form.submit()"
                        class="w-48 sm:w-56 px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden shadow-xs">
                    @forelse ($existingPeriods as $p)
                        <option value="{{ $p }}" {{ $selectedPeriod === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @empty
                        <option value="">-- Belum ada data periode --</option>
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Kelas</label>
                <select name="classroom_id" onchange="this.form.submit()"
                        class="w-44 sm:w-52 px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden shadow-xs">
                    @foreach ($classrooms as $cls)
                        <option value="{{ $cls->id }}" {{ (string) $selectedClassroomId === (string) $cls->id ? 'selected' : '' }}>
                            Kelas {{ $cls->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('reports.batch-export') }}" class="ml-auto inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Ekspor Massal PDF</span>
            </a>
        </form>
    </div>

    @if ($moduleFields->isEmpty())
        <div class="p-12 text-center bg-white border border-slate-200 rounded-2xl">
            <h4 class="text-sm font-bold text-slate-700">Tidak Ada Modul Aktif</h4>
            <p class="text-xs text-slate-400 mt-1">Semua field modul penilaian sedang dinonaktifkan. Aktifkan minimal satu field di Pengaturan Modul untuk memantau kelengkapan.</p>
        </div>
    @elseif ($reports->isEmpty())
        <div class="p-12 text-center bg-white border border-slate-200 rounded-2xl">
            <h4 class="text-sm font-bold text-slate-700">Belum Ada Data Laporan</h4>
            <p class="text-xs text-slate-400 mt-1">Pilih kelas dan periode yang sudah memiliki data laporan bulanan santri.</p>
        </div>
    @else
        <!-- PUBLISH BANNER (admin only) -->
        @if (auth()->user()->hasRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
            <form method="POST" action="{{ route('reports.publish-classroom') }}"
                  onsubmit="return confirm('Terbitkan {{ $readyToPublishCount }} laporan yang sudah lengkap ke portal wali murid?');"
                  class="p-4 rounded-2xl border border-slate-200 bg-white shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
                <input type="hidden" name="period_title" value="{{ $selectedPeriod }}">
                <div class="text-xs text-slate-600">
                    <span class="font-bold text-slate-900">{{ $readyToPublishCount }} laporan</span> sudah lengkap dan belum diterbitkan.
                    Hanya laporan yang lengkap semua modulnya yang akan diterbitkan ke portal wali murid.
                </div>
                <button type="submit" @disabled($readyToPublishCount === 0)
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition disabled:opacity-40 disabled:cursor-not-allowed">
                    Terbitkan yang Lengkap
                </button>
            </form>
        @endif

        <!-- SUMMARY CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($moduleFields->keys() as $module)
                @php
                    $color = $moduleColors[$module] ?? $moduleColors['tahfidz'];
                    $complete = $moduleCompleteCounts[$module] ?? 0;
                    $percent = $totalStudents > 0 ? round(($complete / $totalStudents) * 100) : 0;
                @endphp
                <div class="p-4 rounded-2xl border {{ $color['border'] }} {{ $color['bg'] }} shadow-xs">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $color['dot'] }}"></span>
                        <span class="text-xs font-extrabold {{ $color['text'] }}">{{ \App\Models\ModuleField::MODULES[$module] }}</span>
                    </div>
                    <div class="text-2xl font-black {{ $color['text'] }}">{{ $complete }}<span class="text-sm font-bold text-slate-400">/{{ $totalStudents }}</span></div>
                    <div class="w-full h-1.5 bg-white/70 rounded-full mt-2 overflow-hidden">
                        <div class="h-full {{ $color['bar'] }} rounded-full" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-1.5">{{ $percent }}% santri sudah lengkap</p>
                </div>
            @endforeach
        </div>

        <!-- COMPLETENESS TABLE -->
        <div x-data="{ search: '', onlyIncomplete: false }" class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                <input type="search" x-model="search" placeholder="Cari nama atau NIS..." aria-label="Cari santri"
                       class="w-full sm:w-64 px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" x-model="onlyIncomplete" class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Hanya yang belum lengkap
                </label>
            </div>
            <div class="bg-white border border-slate-200/90 rounded-2xl overflow-x-auto shadow-xs">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="sticky left-0 z-10 bg-slate-50 px-3 sm:px-4 py-2.5 text-left min-w-[160px] sm:min-w-[220px] shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]">Identitas Santri</th>
                            @foreach ($moduleFields->keys() as $module)
                                <th class="px-4 py-2.5 text-center min-w-[130px]">{{ \App\Models\ModuleField::MODULES[$module] }}</th>
                            @endforeach
                            <th class="px-4 py-2.5 text-center min-w-[130px]">Status Keseluruhan</th>
                            <th class="px-4 py-2.5 text-center min-w-[110px]">Status Terbit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $index => $rep)
                            @php
                                $rec = $rep->record;
                                $std = $rep->student;
                                $moduleStatuses = $moduleFields->keys()->mapWithKeys(function ($module) use ($rec) {
                                    $record = $rec ?? new \App\Models\ReportRecord();
                                    return [$module => $record->isModuleComplete($module)];
                                });
                                $allComplete = $moduleStatuses->every(fn ($v) => $v);
                            @endphp
                            <tr class="group hover:bg-slate-50/80 transition-colors"
                                data-search="{{ Str::lower($std->name.' '.$std->nis) }}"
                                x-show="(! onlyIncomplete || {{ $allComplete ? 'false' : 'true' }}) && $el.dataset.search.includes(search.trim().toLowerCase())">
                                <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50 px-3 sm:px-4 py-2.5 font-semibold text-xs text-slate-900 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]">
                                    <span class="text-slate-400 font-mono mr-2">{{ $index + 1 }}.</span>
                                    {{ $std->name }}
                                    <span class="block text-[10px] text-slate-400 font-mono">NIS: {{ $std->nis ?: '-' }}</span>
                                </td>
                                @foreach ($moduleFields->keys() as $module)
                                    <td class="px-4 py-2.5 text-center">
                                        @if ($moduleStatuses[$module])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                &check; Lengkap
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                Belum
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-2.5 text-center">
                                    @if ($allComplete)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">Siap Cetak</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">Belum Lengkap</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @if ($rep->status === 'published')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Terbit</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Draft</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
