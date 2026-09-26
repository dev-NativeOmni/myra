@extends('layouts.app', [
    'header' => 'Kalender Akademik & Hari Libur',
    'subheader' => 'Atur hari efektif halaqah, libur nasional/lembaga, atau libur sebagian per kelas'
])

@section('content')
@php
    $monthsList = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $currentYear = (int) date('Y');
    $yearsList = range($currentYear - 2, $currentYear + 3);
    $todayStr = date('Y-m-d');
@endphp

<!-- Alpine.js Component Script Definition -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('academicCalendarData', () => ({
            selectedHolidays: @json($holidays),
            selectedClassHolidays: @json($classHolidays ?: (object)[]),
            allClassrooms: @json($classrooms),

            modal: {
                isOpen: false,
                dateStr: '',
                dayNum: '',
                type: 'school', // 'school', 'global', 'partial'
                selectedClasses: []
            },

            openModal(dateStr, dayNum) {
                this.modal.dateStr = dateStr;
                this.modal.dayNum = dayNum;

                if (this.selectedHolidays.includes(dateStr)) {
                    this.modal.type = 'global';
                    this.modal.selectedClasses = [];
                } else if (this.selectedClassHolidays[dateStr] && this.selectedClassHolidays[dateStr].length > 0) {
                    this.modal.type = 'partial';
                    this.modal.selectedClasses = [...this.selectedClassHolidays[dateStr]];
                } else {
                    this.modal.type = 'school';
                    this.modal.selectedClasses = [];
                }
                this.modal.isOpen = true;
            },

            saveModal() {
                const dateStr = this.modal.dateStr;
                if (this.modal.type === 'school') {
                    this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
                    delete this.selectedClassHolidays[dateStr];
                } else if (this.modal.type === 'global') {
                    if (!this.selectedHolidays.includes(dateStr)) {
                        this.selectedHolidays.push(dateStr);
                    }
                    delete this.selectedClassHolidays[dateStr];
                } else if (this.modal.type === 'partial') {
                    this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
                    if (this.modal.selectedClasses.length > 0) {
                        this.selectedClassHolidays[dateStr] = [...this.modal.selectedClasses];
                    } else {
                        delete this.selectedClassHolidays[dateStr];
                    }
                }
                this.modal.isOpen = false;
            }
        }));
    });
</script>

<div class="space-y-6" x-data="academicCalendarData">
    <!-- Top Toolbar -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Navigation Buttons -->
        <div class="flex items-center gap-3">
            <a href="{{ route('academic-calendar.index', ['year' => date('Y'), 'month' => date('m')]) }}"
               class="px-3.5 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Hari Ini</span>
            </a>

            <div class="flex items-center bg-slate-100 rounded-xl p-0.5 border border-slate-200">
                <a href="{{ route('academic-calendar.index', ['year' => $prevYear, 'month' => $prevMonth]) }}"
                   title="Bulan Sebelumnya"
                   class="w-8 h-8 flex items-center justify-center text-slate-700 hover:bg-white rounded-lg text-base font-bold transition">
                    &lsaquo;
                </a>
                <a href="{{ route('academic-calendar.index', ['year' => $nextYear, 'month' => $nextMonth]) }}"
                   title="Bulan Berikutnya"
                   class="w-8 h-8 flex items-center justify-center text-slate-700 hover:bg-white rounded-lg text-base font-bold transition">
                    &rsaquo;
                </a>
            </div>

            <h3 class="text-base sm:text-lg font-bold text-slate-900 ml-1">
                {{ $monthsList[$month] }} {{ $year }}
            </h3>
        </div>

        <!-- Quick Jump & Submit Form -->
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('academic-calendar.index') }}" class="flex items-center gap-1.5">
                <select name="month" onchange="this.form.submit()" class="rounded-xl border border-slate-300 text-xs py-1.5 px-2.5 font-semibold text-slate-700 bg-white">
                    @foreach ($monthsList as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="rounded-xl border border-slate-300 text-xs py-1.5 px-2.5 font-semibold text-slate-700 bg-white">
                    @foreach ($yearsList as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>

            <button type="submit" form="calendar-form"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Simpan Kalender</span>
            </button>
        </div>
    </div>

    <!-- Legend Bar -->
    <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
        <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px]">Keterangan Status:</span>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-emerald-500 border border-emerald-600"></span>
            <span>Hari Sekolah / Halaqah</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-rose-500 border border-rose-600"></span>
            <span>Libur Nasional / Lembaga (Semua Kelas)</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-amber-500 border border-amber-600"></span>
            <span>Libur Sebagian / Khusus Kelas</span>
        </div>
    </div>

    <!-- Calendar Grid Container -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs overflow-hidden">
        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-2 text-center text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">
            <div><span class="sm:hidden">Sen</span><span class="hidden sm:inline">Senin</span></div>
            <div><span class="sm:hidden">Sel</span><span class="hidden sm:inline">Selasa</span></div>
            <div><span class="sm:hidden">Rab</span><span class="hidden sm:inline">Rabu</span></div>
            <div><span class="sm:hidden">Kam</span><span class="hidden sm:inline">Kamis</span></div>
            <div><span class="sm:hidden">Jum</span><span class="hidden sm:inline">Jumat</span></div>
            <div><span class="sm:hidden">Sab</span><span class="hidden sm:inline">Sabtu</span></div>
            <div class="text-rose-600"><span class="sm:hidden">Ahd</span><span class="hidden sm:inline">Ahad</span></div>
        </div>

        <!-- Date Cells Grid -->
        <div class="grid grid-cols-7 gap-1 sm:gap-2">
            @foreach ($calendarDays as $cell)
                @if ($cell['type'] === 'padding')
                    <div class="min-h-[56px] sm:min-h-[90px] rounded-lg sm:rounded-xl bg-slate-50/50 border border-transparent"></div>
                @else
                    @php
                        $dStr = $cell['date'];
                        $isToday = ($dStr === $todayStr);
                    @endphp
                    <div
                        @click="openModal('{{ $dStr }}', {{ $cell['day'] }})"
                        class="min-h-[56px] sm:min-h-[90px] p-1.5 sm:p-2.5 rounded-lg sm:rounded-xl border transition cursor-pointer flex flex-col justify-between select-none relative group hover:shadow-xs"
                        :class="
                            selectedHolidays.includes('{{ $dStr }}')
                                ? 'bg-rose-50 border-rose-200 hover:border-rose-300'
                                : (selectedClassHolidays['{{ $dStr }}'] && selectedClassHolidays['{{ $dStr }}'].length > 0
                                    ? 'bg-amber-50 border-amber-200 hover:border-amber-300'
                                    : '{{ $cell['is_weekend'] ? 'bg-slate-50/70 border-slate-200' : 'bg-white border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/20' }}')
                        "
                    >
                        <!-- Top Day Header -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black {{ $isToday ? 'w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center' : ($cell['is_weekend'] ? 'text-rose-600' : 'text-slate-800') }}">
                                {{ $cell['day'] }}
                            </span>

                            <template x-if="selectedHolidays.includes('{{ $dStr }}')">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            </template>
                            <template x-if="!selectedHolidays.includes('{{ $dStr }}') && selectedClassHolidays['{{ $dStr }}'] && selectedClassHolidays['{{ $dStr }}'].length > 0">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            </template>
                        </div>

                        <!-- Status Label -->
                        <div class="hidden sm:block mt-2 text-[10px]">
                            <template x-if="selectedHolidays.includes('{{ $dStr }}')">
                                <span class="inline-block px-1.5 py-0.5 rounded-sm font-bold bg-rose-100 text-rose-800 truncate w-full text-center">
                                    Libur Total
                                </span>
                            </template>

                            <template x-if="!selectedHolidays.includes('{{ $dStr }}') && selectedClassHolidays['{{ $dStr }}'] && selectedClassHolidays['{{ $dStr }}'].length > 0">
                                <span class="inline-block px-1.5 py-0.5 rounded-sm font-bold bg-amber-100 text-amber-800 truncate w-full text-center"
                                      x-text="selectedClassHolidays['{{ $dStr }}'].length + ' Kelas Libur'">
                                </span>
                            </template>

                            <template x-if="!selectedHolidays.includes('{{ $dStr }}') && (!selectedClassHolidays['{{ $dStr }}'] || selectedClassHolidays['{{ $dStr }}'].length === 0)">
                                <span class="text-[10px] text-slate-400 group-hover:text-emerald-700">
                                    {{ $cell['is_weekend'] ? 'Akhir Pekan' : 'Hari Aktif' }}
                                </span>
                            </template>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Hidden Form for Persistence -->
    <form id="calendar-form" method="POST" action="{{ route('academic-calendar.update') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="holidays" :value="JSON.stringify(selectedHolidays)">
        <input type="hidden" name="class_holidays" :value="JSON.stringify(selectedClassHolidays)">
    </form>

    <!-- Date Setting Modal -->
    <div x-show="modal.isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="modal.isOpen = false"
             class="bg-white rounded-2xl max-w-md w-full p-5 sm:p-6 shadow-xl space-y-4 border border-slate-200">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-base text-slate-900">Pengaturan Hari Kalender</h3>
                    <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="'Tanggal: ' + modal.dateStr"></p>
                </div>
                <button type="button" @click="modal.isOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <!-- Radio Selection -->
            <div class="space-y-2.5">
                <!-- Option 1: Hari Sekolah / Halaqah Aktif -->
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-emerald-300 transition cursor-pointer bg-slate-50/50">
                    <input type="radio" name="modal_type" value="school" x-model="modal.type" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <div class="font-bold text-xs text-slate-800">Hari Sekolah / Halaqah Aktif</div>
                        <div class="text-[11px] text-slate-500">Seluruh kelas aktif mengikuti kegiatan belajar & halaqah.</div>
                    </div>
                </label>

                <!-- Option 2: Libur Nasional / Lembaga (Semua Kelas) -->
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-rose-300 transition cursor-pointer bg-slate-50/50">
                    <input type="radio" name="modal_type" value="global" x-model="modal.type" class="w-4 h-4 text-rose-600 focus:ring-rose-500">
                    <div>
                        <div class="font-bold text-xs text-rose-800">Libur Nasional / Lembaga (Total)</div>
                        <div class="text-[11px] text-slate-500">Seluruh santri dan kelas diliburkan serentak.</div>
                    </div>
                </label>

                <!-- Option 3: Libur Khusus Kelas Tertentu -->
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-amber-300 transition cursor-pointer bg-slate-50/50">
                    <input type="radio" name="modal_type" value="partial" x-model="modal.type" class="w-4 h-4 text-amber-600 focus:ring-amber-500">
                    <div>
                        <div class="font-bold text-xs text-amber-800">Libur Khusus Kelas Tertentu</div>
                        <div class="text-[11px] text-slate-500">Hanya kelas tertentu yang diliburkan pada tanggal ini.</div>
                    </div>
                </label>
            </div>

            <!-- Class Selection Checkboxes when Partial is selected -->
            <div x-show="modal.type === 'partial'" class="pt-2 border-t border-slate-100 space-y-2">
                <span class="text-xs font-bold text-slate-700 block">Pilih Kelas yang Diliburkan:</span>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="c in allClassrooms" :key="c.id">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-slate-50 text-xs font-medium cursor-pointer">
                            <input type="checkbox" :value="c.id" x-model="modal.selectedClasses" class="w-3.5 h-3.5 text-amber-600 rounded-sm">
                            <span x-text="'Kelas ' + c.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" @click="modal.isOpen = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="saveModal()"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer">
                    Terapkan
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
