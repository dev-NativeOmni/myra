@extends('layouts.app', [
    'header' => 'Portal Input Modul Penilaian',
    'subheader' => 'Input data terpadu Tahfidz, Kesantrian, Wali '.\App\Models\Institution::term('class').', dan Tata Usaha (TU) dalam satu mode spreadsheet'
])

@section('content')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('moduleSpreadsheetData', () => ({
            selectedTab: '{{ $selectedTab }}',
            selectedClass: '{{ $selectedClassroomId }}',
            selectedPeriod: '{{ $selectedPeriod }}',
            isDirty: false,
            isSaving: false,
            filterSearch: '',

            init() {
                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty && !this.isSaving) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                document.addEventListener('click', (e) => {
                    const link = e.target.closest('a');
                    if (link && link.href && !link.target && !link.hasAttribute('download') && this.isDirty && !this.isSaving) {
                        if (!confirm('Peringatan: Ada perubahan data yang belum disimpan. Jika Anda berpindah halaman, perubahan tersebut akan hilang. Lanjutkan keluar?')) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    }
                });
            },

            setTab(tab) {
                this.selectedTab = tab;
            },

            quickFillAll(field, value) {
                if (!confirm(`Terapkan nilai "${value}" untuk seluruh santri pada kolom ini?`)) return;
                const inputs = document.querySelectorAll(`[data-field="${field}"]`);
                inputs.forEach(input => {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                this.isDirty = true;
            },

            submitForm() {
                if (this.isSaving) return;
                this.isSaving = true;
                this.isDirty = false;
                document.getElementById('module-spreadsheet-form').submit();
            }
        }));
    });
</script>

@php
    $userEditableModules = auth()->user()->editableModules();
    $canEditThisClassroom = auth()->user()->canEditClassroom($selectedClassroomId ? (int) $selectedClassroomId : null);
    $canEditTahfidz = $canEditThisClassroom && in_array(\App\Models\ModuleField::MODULE_TAHFIDZ, $userEditableModules, true);
    $canEditKesantrian = $canEditThisClassroom && in_array(\App\Models\ModuleField::MODULE_KESANTRIAN, $userEditableModules, true);
    $canEditAkademik = $canEditThisClassroom && in_array(\App\Models\ModuleField::MODULE_AKADEMIK, $userEditableModules, true);
    $canEditAdministrasi = $canEditThisClassroom && in_array(\App\Models\ModuleField::MODULE_ADMINISTRASI, $userEditableModules, true);
@endphp

<div class="space-y-5" x-data="moduleSpreadsheetData">
    <!-- TOP TOOLBAR & FILTER -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        <form method="GET" action="{{ route('modules.spreadsheet') }}" id="filter-form" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <input type="hidden" name="tab" :value="selectedTab">

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Period Selector -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Periode Rapor</label>
                    <div class="relative">
                        <select name="period_title" onchange="this.form.submit()"
                                class="w-48 sm:w-56 px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden shadow-xs">
                            @foreach ($existingPeriods as $p)
                                <option value="{{ $p }}" {{ $selectedPeriod === $p ? 'selected' : '' }}>
                                    {{ $p }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Classroom Selector -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Kelas Halaqah</label>
                    <select name="classroom_id" onchange="this.form.submit()"
                            class="w-44 sm:w-52 px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden shadow-xs">
                        @foreach ($classrooms as $cls)
                            <option value="{{ $cls->id }}" {{ (string)$selectedClassroomId === (string)$cls->id ? 'selected' : '' }}>
                                Kelas {{ $cls->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input for Filtering in Table -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Cari Nama Santri</label>
                    <div class="relative">
                        <input type="text" x-model="filterSearch" placeholder="Ketik nama..."
                                class="w-40 sm:w-48 px-3 py-2 pl-8 rounded-xl border border-slate-300 text-xs font-medium text-slate-800 bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden shadow-xs">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3 justify-end">
                <span x-show="isDirty" class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 animate-pulse" style="display: none;">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Ada perubahan belum disimpan
                </span>

                <button type="button" @click="submitForm()"
                        :disabled="isSaving"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Semua Data'"></span>
                </button>
            </div>
        </form>

        <!-- MODULE TABS -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 flex-wrap bg-slate-100 p-1 rounded-xl border border-slate-200/80">
                <!-- Tab: Semua Modul -->
                <button type="button" @click="setTab('all')"
                        :class="selectedTab === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    <span>Semua Modul</span>
                </button>

                <!-- Tab: Tahfidz -->
                <button type="button" @click="setTab('tahfidz')"
                        :class="selectedTab === 'tahfidz' ? 'bg-white text-emerald-900 font-bold shadow-xs' : 'text-slate-600 hover:text-emerald-900 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Tahfidz</span>
                </button>

                <!-- Tab: Kesantrian -->
                <button type="button" @click="setTab('kesantrian')"
                        :class="selectedTab === 'kesantrian' ? 'bg-white text-blue-900 font-bold shadow-xs' : 'text-slate-600 hover:text-blue-900 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Kesantrian</span>
                </button>

                <!-- Tab: Wali Kelas -->
                <button type="button" @click="setTab('akademik')"
                        :class="selectedTab === 'akademik' ? 'bg-white text-amber-900 font-bold shadow-xs' : 'text-slate-600 hover:text-amber-900 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Wali Kelas (KBM)</span>
                </button>

                <!-- Tab: Tata Usaha (TU) -->
                <button type="button" @click="setTab('administrasi')"
                        :class="selectedTab === 'administrasi' ? 'bg-white text-purple-900 font-bold shadow-xs' : 'text-slate-600 hover:text-purple-900 font-medium'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Tata Usaha (TU)</span>
                </button>
            </div>

            <div class="text-[11px] text-slate-500">
                Menampilkan <span class="font-bold text-slate-800">{{ $reports->count() }}</span> Santri aktif
            </div>
        </div>
    </div>

    @if ($reports->isEmpty())
        <div class="p-12 text-center bg-white border border-slate-200 rounded-2xl">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h4 class="text-sm font-bold text-slate-700">Belum Ada Santri di Kelas Ini</h4>
            <p class="text-xs text-slate-400 mt-1">Silakan pilih kelas lain atau tambahkan santri aktif pada menu Data Santri.</p>
        </div>
    @else
        <!-- SPREADSHEET FORM -->
        <form id="module-spreadsheet-form" method="POST" action="{{ route('modules.batch-store') }}" @input="isDirty = true" @change="isDirty = true">
            @csrf
            <input type="hidden" name="classroom_id" value="{{ $selectedClassroomId }}">
            <input type="hidden" name="period_title" value="{{ $selectedPeriod }}">
            <input type="hidden" name="tab" :value="selectedTab">

            <!-- ========================================== -->
            <!-- DESKTOP / TABLET SPREADSHEET MATRIX VIEW   -->
            <!-- ========================================== -->
            <div class="hidden md:block isolate bg-white border border-slate-200/90 rounded-2xl overflow-x-auto overflow-y-auto touch-scroll max-h-[calc(100vh-14rem)] shadow-xs">
                <table class="min-w-full divide-y divide-slate-200 border-collapse">
                    <thead class="sticky top-0 z-30 bg-slate-100 shadow-xs">
                        <!-- Group Header Row -->
                        <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                            <th rowspan="2" class="sticky top-0 left-0 z-40 bg-slate-100 px-4 py-2 text-left min-w-[220px] w-60 border-r-2 border-b border-slate-300 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]">
                                <div class="flex items-center gap-3">
                                    <span class="w-5 text-center text-[10px] font-mono text-slate-400">NO</span>
                                    <span>Identitas Santri</span>
                                </div>
                            </th>

                            <!-- TAHFIDZ SECTION -->
                            @if ($tahfidzFields->isNotEmpty())
                            <template x-if="selectedTab === 'all' || selectedTab === 'tahfidz'">
                                <th colspan="{{ $tahfidzFields->count() }}" class="px-4 py-2 text-center bg-emerald-50/90 text-emerald-900 border-r-2 border-b border-emerald-200 font-extrabold">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span>Modul Tahfidz</span>
                                    </div>
                                </th>
                            </template>
                            @endif

                            <!-- KESANTRIAN SECTION -->
                            @if ($kesantrianFields->isNotEmpty())
                            <template x-if="selectedTab === 'all' || selectedTab === 'kesantrian'">
                                <th colspan="{{ $kesantrianFields->count() }}" class="px-4 py-2 text-center bg-blue-50/90 text-blue-900 border-r-2 border-b border-blue-200 font-extrabold">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                        <span>Modul Kesantrian</span>
                                    </div>
                                </th>
                            </template>
                            @endif

                            <!-- WALI KELAS SECTION -->
                            @if ($akademikFields->isNotEmpty())
                            <template x-if="selectedTab === 'all' || selectedTab === 'akademik'">
                                <th colspan="{{ $akademikFields->count() }}" class="px-4 py-2 text-center bg-amber-50/90 text-amber-900 border-r-2 border-b border-amber-200 font-extrabold">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span>Wali Kelas (Evaluasi KBM)</span>
                                    </div>
                                </th>
                            </template>
                            @endif

                            <!-- TATA USAHA (TU) SECTION -->
                            @if ($administrasiFields->isNotEmpty())
                            <template x-if="selectedTab === 'all' || selectedTab === 'administrasi'">
                                <th colspan="{{ $administrasiFields->count() }}" class="px-4 py-2 text-center bg-purple-50/90 text-purple-900 border-b border-purple-200 font-extrabold">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                        <span>Tata Usaha (Administrasi & SPP)</span>
                                    </div>
                                </th>
                            </template>
                            @endif
                        </tr>

                        <!-- Sub-Column Headers Row -->
                        <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-50 border-b border-slate-300">
                            <!-- Tahfidz Sub-headers -->
                            @foreach ($tahfidzFields as $fld)
                                <template x-if="selectedTab === 'all' || selectedTab === 'tahfidz'">
                                    <th class="px-2.5 py-2 text-left bg-emerald-50/40 {{ $loop->last ? 'border-r-2 border-slate-300' : 'border-r border-slate-200' }} {{ $fld->type === 'textarea' ? 'min-w-[260px] w-72' : ($fld->type === 'number' ? 'min-w-[80px] w-24 text-center' : 'min-w-[130px] w-36') }}" title="{{ $fld->label }}">
                                        @if ($fld->type === 'select' && !empty($fld->options))
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="truncate">{{ $fld->label }}</span>
                                                <button type="button" @click="quickFillAll('{{ $fld->key }}', '{{ $fld->options[0] ?? '' }}')" @disabled(!$canEditTahfidz) class="px-1.5 py-0.5 rounded-sm bg-emerald-100 hover:bg-emerald-200 text-[9px] font-bold text-emerald-700 transition disabled:opacity-40 disabled:cursor-not-allowed" title="Isi semua {{ $fld->options[0] ?? '' }}">Auto</button>
                                            </div>
                                        @else
                                            <span>{{ $fld->label }}</span>
                                        @endif
                                    </th>
                                </template>
                            @endforeach

                            <!-- Kesantrian Sub-headers -->
                            @foreach ($kesantrianFields as $fld)
                                <template x-if="selectedTab === 'all' || selectedTab === 'kesantrian'">
                                    <th class="px-2.5 py-2 {{ $fld->type === 'number' ? 'text-center min-w-[80px] w-24' : ($fld->type === 'textarea' ? 'text-left min-w-[260px] w-72' : 'text-center min-w-[130px] w-36') }} {{ $loop->last ? 'border-r-2 border-slate-300' : 'border-r border-slate-200' }} bg-blue-50/40" title="{{ $fld->label }}">
                                        @if ($fld->type === 'select' && !empty($fld->options) && count($fld->options) > 2)
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="truncate">{{ $fld->label }}</span>
                                                <button type="button" @click="quickFillAll('{{ $fld->key }}', '{{ $fld->options[0] ?? '' }}')" @disabled(!$canEditKesantrian) class="px-1.5 py-0.5 rounded-sm bg-blue-100 hover:bg-blue-200 text-[9px] font-bold text-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed" title="Isi semua {{ $fld->options[0] ?? '' }}">Auto A</button>
                                            </div>
                                        @else
                                            <span>{{ $fld->label }}</span>
                                        @endif
                                    </th>
                                </template>
                            @endforeach

                            <!-- Wali Kelas Sub-headers -->
                            @foreach ($akademikFields as $fld)
                                <template x-if="selectedTab === 'all' || selectedTab === 'akademik'">
                                    <th class="px-3 py-2 text-left min-w-[260px] w-72 border-r-2 border-slate-300 bg-amber-50/40" title="{{ $fld->label }}">{{ $fld->label }}</th>
                                </template>
                            @endforeach

                            <!-- TU Sub-headers -->
                            @foreach ($administrasiFields as $fld)
                                <template x-if="selectedTab === 'all' || selectedTab === 'administrasi'">
                                    <th class="px-2.5 py-2 text-left min-w-[130px] w-36 {{ $loop->last ? 'border-slate-200' : 'border-r border-slate-200' }} bg-purple-50/40" title="{{ $fld->label }}">{{ $fld->label }}</th>
                                </template>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($reports as $index => $rep)
                            @php
                                $rec = $rep->record;
                                $std = $rep->student;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors"
                                x-show="!filterSearch || '{{ strtolower(addslashes($std->name)) }}'.includes(filterSearch.toLowerCase()) || '{{ $std->nis }}'.includes(filterSearch.toLowerCase())">
                                
                                <!-- Identitas Santri (Sticky Col) -->
                                <td class="sticky left-0 z-20 bg-white px-4 py-2.5 border-r-2 border-b border-slate-300 font-bold text-xs text-slate-900 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]">
                                    <div class="flex items-center gap-3">
                                        <span class="shrink-0 w-5 text-center text-xs font-bold text-slate-400 font-mono">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1">
                                            <span class="block truncate" title="{{ $std->name }}">{{ $std->name }}</span>
                                            <span class="block text-[10px] text-slate-400 font-mono mt-0.5">NIS: {{ $std->nis ?: '-' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- TAHFIDZ INPUTS -->
                                @foreach ($tahfidzFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <template x-if="selectedTab === 'all' || selectedTab === 'tahfidz'">
                                        <td class="p-1.5 {{ $loop->last ? 'border-r-2 border-slate-300' : 'border-r border-slate-200' }} border-b bg-emerald-50/10 align-middle">
                                            @if ($fld->type === 'select')
                                                <select name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditTahfidz)
                                                        class="w-full min-w-[125px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 bg-white focus:ring-1 focus:ring-emerald-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                                    <option value="">{{ $fld->placeholder ?: '- Pilih -' }}</option>
                                                    @foreach ($fld->options ?? [] as $opt)
                                                        <option value="{{ $opt }}" {{ $val === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($fld->type === 'number')
                                                <input type="number" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditTahfidz)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? '0' }}"
                                                       class="w-full min-w-[65px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono text-center text-slate-800 bg-white focus:ring-1 focus:ring-emerald-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @elseif ($fld->type === 'textarea')
                                                <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" rows="2" @disabled(!$canEditTahfidz)
                                                          placeholder="{{ $fld->placeholder ?? 'Catatan...' }}"
                                                          class="w-full min-w-[240px] px-2 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-emerald-500 outline-hidden resize-y disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                            @else
                                                <input type="text" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditTahfidz)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? '' }}"
                                                       class="w-full min-w-[120px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:ring-1 focus:ring-emerald-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @endif
                                        </td>
                                    </template>
                                @endforeach

                                <!-- KESANTRIAN INPUTS -->
                                @foreach ($kesantrianFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <template x-if="selectedTab === 'all' || selectedTab === 'kesantrian'">
                                        <td class="p-1.5 {{ $loop->last ? 'border-r-2 border-slate-300' : 'border-r border-slate-200' }} border-b bg-blue-50/10 align-middle">
                                            @if ($fld->type === 'select')
                                                <select name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditKesantrian)
                                                        class="w-full min-w-[125px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 bg-white focus:ring-1 focus:ring-blue-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                                    <option value="">{{ $fld->placeholder ?: '- Pilih -' }}</option>
                                                    @foreach ($fld->options ?? [] as $opt)
                                                        <option value="{{ $opt }}" {{ ($val === $opt || ($fld->key === 'is_baligh' && !$val && $opt === 'Belum')) ? 'selected' : '' }}>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($fld->type === 'number')
                                                <input type="number" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditKesantrian)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? 'cm' }}"
                                                       class="w-full min-w-[65px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-mono text-center text-slate-800 bg-white focus:ring-1 focus:ring-blue-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @elseif ($fld->type === 'textarea')
                                                <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" rows="2" @disabled(!$canEditKesantrian)
                                                          placeholder="{{ $fld->placeholder ?? 'Catatan kesantrian...' }}"
                                                          class="w-full min-w-[260px] px-2 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-blue-500 outline-hidden resize-y disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                            @else
                                                <input type="text" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditKesantrian)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? '' }}"
                                                       class="w-full min-w-[120px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-800 bg-white focus:ring-1 focus:ring-blue-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @endif
                                        </td>
                                    </template>
                                @endforeach

                                <!-- WALI KELAS INPUTS -->
                                @foreach ($akademikFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <template x-if="selectedTab === 'all' || selectedTab === 'akademik'">
                                        <td class="p-1.5 border-r-2 border-b border-slate-300 bg-amber-50/10 align-middle">
                                            @if ($fld->type === 'textarea')
                                                <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" rows="2" @disabled(!$canEditAkademik)
                                                          placeholder="{{ $fld->placeholder ?? 'Catatan KBM...' }}"
                                                          class="w-full min-w-[260px] px-2 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-amber-500 outline-hidden resize-y disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                            @else
                                                <input type="text" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditAkademik)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? '' }}"
                                                       class="w-full min-w-[130px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-amber-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @endif
                                        </td>
                                    </template>
                                @endforeach

                                <!-- TATA USAHA (TU) INPUTS -->
                                @foreach ($administrasiFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <template x-if="selectedTab === 'all' || selectedTab === 'administrasi'">
                                        <td class="p-1.5 {{ $loop->last ? 'border-b' : 'border-r border-b' }} border-slate-200 bg-purple-50/10 align-middle">
                                            @if ($fld->type === 'select')
                                                <select name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditAdministrasi)
                                                        class="w-full min-w-[125px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 bg-white focus:ring-1 focus:ring-purple-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                                    <option value="">{{ $fld->placeholder ?: '- Pilih -' }}</option>
                                                    @foreach ($fld->options ?? [] as $opt)
                                                        <option value="{{ $opt }}" {{ $val === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($fld->type === 'textarea')
                                                <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" rows="2" @disabled(!$canEditAdministrasi)
                                                          placeholder="{{ $fld->placeholder ?? 'Catatan administrasi...' }}"
                                                          class="w-full min-w-[200px] px-2 py-1 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-purple-500 outline-hidden resize-y disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                            @else
                                                <input type="text" name="records[{{ $rep->id }}][{{ $fld->key }}]" data-field="{{ $fld->key }}" @disabled(!$canEditAdministrasi)
                                                       value="{{ $val }}" placeholder="{{ $fld->placeholder ?? "Misal: {$selectedPeriod}" }}"
                                                       class="w-full min-w-[130px] px-2 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800 bg-white focus:ring-1 focus:ring-purple-500 outline-hidden disabled:bg-slate-50 disabled:text-slate-500">
                                            @endif
                                        </td>
                                    </template>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ========================================== -->
            <!-- MOBILE VIEW (Layar HP / Responsif)         -->
            <!-- ========================================== -->
            <div class="md:hidden space-y-4">
                @foreach ($reports as $rep)
                    @php
                        $rec = $rep->record;
                        $std = $rep->student;
                    @endphp
                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs space-y-3"
                         x-show="!filterSearch || '{{ strtolower(addslashes($std->name)) }}'.includes(filterSearch.toLowerCase()) || '{{ $std->nis }}'.includes(filterSearch.toLowerCase())">
                        
                        <!-- Header Santri -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div>
                                <h4 class="font-bold text-sm text-slate-900">{{ $std->name }}</h4>
                                <p class="text-[10px] text-slate-400 font-mono">NIS: {{ $std->nis ?: '-' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-sm bg-emerald-50 text-emerald-800 font-bold text-[10px] border border-emerald-200">
                                {{ $selectedPeriod }}
                            </span>
                        </div>

                        <!-- TAHFIDZ (Mobile) -->
                        @if ($tahfidzFields->isNotEmpty())
                        <div x-show="selectedTab === 'all' || selectedTab === 'tahfidz'" class="space-y-2.5 p-3 rounded-xl bg-emerald-50/30 border border-emerald-100">
                            <h5 class="text-xs font-extrabold text-emerald-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>Tahfidz</span>
                            </h5>
                            <div class="space-y-2">
                                @foreach ($tahfidzFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">{{ $fld->label }}</label>
                                        @if ($fld->type === 'select')
                                            <select name="records[{{ $rep->id }}][{{ $fld->key }}]" @disabled(!$canEditTahfidz) class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                                <option value="">{{ $fld->placeholder ?: '-' }}</option>
                                                @foreach ($fld->options ?? [] as $opt)
                                                    <option value="{{ $opt }}" {{ $val === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($fld->type === 'textarea')
                                            <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" rows="2" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditTahfidz) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                        @else
                                            <input type="{{ $fld->type === 'number' ? 'number' : 'text' }}" name="records[{{ $rep->id }}][{{ $fld->key }}]" value="{{ $val }}" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditTahfidz) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- KESANTRIAN (Mobile) -->
                        @if ($kesantrianFields->isNotEmpty())
                        <div x-show="selectedTab === 'all' || selectedTab === 'kesantrian'" class="space-y-2.5 p-3 rounded-xl bg-blue-50/30 border border-blue-100">
                            <h5 class="text-xs font-extrabold text-blue-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span>Kesantrian</span>
                            </h5>
                            <div class="space-y-2">
                                @foreach ($kesantrianFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">{{ $fld->label }}</label>
                                        @if ($fld->type === 'select')
                                            <select name="records[{{ $rep->id }}][{{ $fld->key }}]" @disabled(!$canEditKesantrian) class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                                <option value="">{{ $fld->placeholder ?: '-' }}</option>
                                                @foreach ($fld->options ?? [] as $opt)
                                                    <option value="{{ $opt }}" {{ ($val === $opt || ($fld->key === 'is_baligh' && !$val && $opt === 'Belum')) ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($fld->type === 'textarea')
                                            <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" rows="2" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditKesantrian) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                        @else
                                            <input type="{{ $fld->type === 'number' ? 'number' : 'text' }}" name="records[{{ $rep->id }}][{{ $fld->key }}]" value="{{ $val }}" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditKesantrian) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- WALI KELAS (Mobile) -->
                        @if ($akademikFields->isNotEmpty())
                        <div x-show="selectedTab === 'all' || selectedTab === 'akademik'" class="space-y-2 p-3 rounded-xl bg-amber-50/30 border border-amber-100">
                            <h5 class="text-xs font-extrabold text-amber-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Wali Kelas (Evaluasi KBM)</span>
                            </h5>
                            @foreach ($akademikFields as $fld)
                                @php $val = $rec?->getFieldValue($fld->key); @endphp
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">{{ $fld->label }}</label>
                                    @if ($fld->type === 'textarea')
                                        <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" rows="2" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditAkademik) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                    @else
                                        <input type="text" name="records[{{ $rep->id }}][{{ $fld->key }}]" value="{{ $val }}" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditAkademik) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @endif

                        <!-- TU (Mobile) -->
                        @if ($administrasiFields->isNotEmpty())
                        <div x-show="selectedTab === 'all' || selectedTab === 'administrasi'" class="space-y-2.5 p-3 rounded-xl bg-purple-50/30 border border-purple-100">
                            <h5 class="text-xs font-extrabold text-purple-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span>Tata Usaha (TU)</span>
                            </h5>
                            <div class="space-y-2">
                                @foreach ($administrasiFields as $fld)
                                    @php $val = $rec?->getFieldValue($fld->key); @endphp
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-0.5">{{ $fld->label }}</label>
                                        @if ($fld->type === 'select')
                                            <select name="records[{{ $rep->id }}][{{ $fld->key }}]" @disabled(!$canEditAdministrasi) class="w-full px-2 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                                <option value="">{{ $fld->placeholder ?: '-' }}</option>
                                                @foreach ($fld->options ?? [] as $opt)
                                                    <option value="{{ $opt }}" {{ $val === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        @elseif ($fld->type === 'textarea')
                                            <textarea name="records[{{ $rep->id }}][{{ $fld->key }}]" rows="2" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditAdministrasi) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">{{ $val }}</textarea>
                                        @else
                                            <input type="{{ $fld->type === 'number' ? 'number' : 'text' }}" name="records[{{ $rep->id }}][{{ $fld->key }}]" value="{{ $val }}" placeholder="{{ $fld->placeholder }}" @disabled(!$canEditAdministrasi) class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- WALI KELAS (Mobile) -->
                        <div x-show="selectedTab === 'all' || selectedTab === 'akademik'" class="space-y-2 p-3 rounded-xl bg-amber-50/30 border border-amber-100">
                            <h5 class="text-xs font-extrabold text-amber-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>Wali Kelas (Evaluasi KBM)</span>
                            </h5>
                            <textarea name="records[{{ $rep->id }}][academic_notes]" rows="2"
                                      placeholder="Catatan perkembangan KBM santri..." @disabled(!$canEditAkademik)
                                      class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">{{ $rec?->academic_notes }}</textarea>
                        </div>

                        <!-- TU (Mobile) -->
                        <div x-show="selectedTab === 'all' || selectedTab === 'administrasi'" class="space-y-2.5 p-3 rounded-xl bg-purple-50/30 border border-purple-100">
                            <h5 class="text-xs font-extrabold text-purple-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span>Tata Usaha (TU)</span>
                            </h5>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">SPP Terakhir</label>
                                    <input type="text" name="records[{{ $rep->id }}][last_spp]" value="{{ $rec?->last_spp }}"
                                           placeholder="Misal: {{ $selectedPeriod }}" @disabled(!$canEditAdministrasi)
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Laundry Terakhir</label>
                                    <input type="text" name="records[{{ $rep->id }}][last_laundry]" value="{{ $rec?->last_laundry }}"
                                           placeholder="Misal: {{ $selectedPeriod }}" @disabled(!$canEditAdministrasi)
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Daftar Ulang</label>
                                    <input type="text" name="records[{{ $rep->id }}][registration_status]" value="{{ $rec?->registration_status }}"
                                           placeholder="Lunas / Belum" @disabled(!$canEditAdministrasi)
                                           class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white disabled:bg-slate-50 disabled:text-slate-500">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- BOTTOM SUBMIT BAR -->
            <div class="sticky bottom-4 z-40 bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-4 shadow-lg flex items-center justify-between gap-4 mt-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full" :class="isDirty ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'"></span>
                    <span class="text-xs font-semibold text-slate-600" x-text="isDirty ? 'Ada perubahan belum disimpan' : 'Semua data tersimpan'"></span>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="submitForm()"
                            :disabled="isSaving"
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="isSaving ? 'Menyimpan...' : 'Simpan Semua Data (Batch Save)'"></span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

