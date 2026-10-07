@extends('layouts.app', [
    'header' => 'Input Spreadsheet Jurnal Tahfidz',
    'subheader' => 'Pencatatan perkembangan setoran & presensi halaqah santri secara cepat berbasis matriks tanggal'
])

@section('content')
<!-- Alpine.js Component Script Definition -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('spreadsheetData', () => ({
            selectedClass: '{{ $selectedClassroomId }}',
            selectedMonth: '{{ $selectedMonth }}',
            selectedWeek: '{{ $selectedWeek }}',
            todayDate: '{{ now()->format('Y-m-d') }}',
            currentMonth: '{{ now()->format('Y-m') }}',
            selectedMobileDate: '{{ in_array(now()->format('Y-m-d'), $dates) ? now()->format('Y-m-d') : ($dates[0] ?? '') }}',
            students: @json($students),
            surahs: @json($surahs),
            dates: @json($dates),
            columns: @json($columns),
            attendancesMap: @json($attendancesMap),
            hafalanRecordsMap: @json($hafalanRecordsMap),
            lastHafalanMap: @json($lastHafalanMap),
            gridData: {},
            surahDetails: {},
            isDirty: false,
            isSaving: false,
            isMobileView: window.innerWidth < 768,

            init() {
                window.addEventListener('resize', () => {
                    this.isMobileView = window.innerWidth < 768;
                });

                window.addEventListener('beforeunload', (e) => {
                    if (this.isDirty && !this.isSaving) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                // Warn on internal link click navigation if unsaved changes exist
                document.addEventListener('click', (e) => {
                    const link = e.target.closest('a');
                    if (link && link.href && !link.target && !link.hasAttribute('download') && this.isDirty && !this.isSaving) {
                        if (!confirm('Peringatan: Ada perubahan setoran / presensi di spreadsheet yang belum disimpan. Jika Anda meninggalkan halaman ini, perubahan tersebut akan hilang. Lanjutkan keluar?')) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    }
                });

                // Index surah details for fast lookup
                this.surahs.forEach(s => {
                    this.surahDetails[s.number] = {
                        number: s.number,
                        totalAyah: s.total_ayah,
                        name: s.name_latin,
                        juzStart: s.juz_start
                    };
                });

                // Initialize reactive grid data for all students and dates
                this.students.forEach(s => {
                    this.gridData[s.id] = { dates: {} };
                    this.dates.forEach(d => {
                        let att = (this.attendancesMap[s.id] && this.attendancesMap[s.id][d]) ? this.attendancesMap[s.id][d] : '';

                        let hList = [];
                        if (this.hafalanRecordsMap[s.id] && this.hafalanRecordsMap[s.id][d]) {
                            hList = JSON.parse(JSON.stringify(this.hafalanRecordsMap[s.id][d]));
                        }
                        if (hList.length === 0) {
                            hList.push({
                                id: null,
                                surah_id: '',
                                surah: '',
                                ayah_start: '',
                                ayah_end: '',
                                type: 'ziyadah',
                                score: '',
                                grade: '',
                                status: 'passed',
                                notes: ''
                            });
                        }

                        if (!att && hList.length > 0 && hList[0].surah_id) {
                            att = 'hadir';
                        }

                        this.gridData[s.id].dates[d] = {
                            attendance: att,
                            hafalans: hList
                        };
                    });
                });

                this.$nextTick(() => {
                    let isReady = true;
                    this.checkDraft();
                    this.$watch('gridData', () => {
                        if (isReady) {
                            this.isDirty = true;
                            this.saveDraftDebounced();
                        }
                    }, { deep: true });

                    // Auto-scroll to today's column on initial view if present
                    if (this.dates.includes(this.todayDate)) {
                        setTimeout(() => {
                            const col = document.getElementById('col-header-' + this.todayDate);
                            if (col) {
                                col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                            }
                        }, 250);
                    }
                });
            },

            draftKey: 'myra_draft_tahfidz_{{ $selectedClassroomId }}_{{ $selectedMonth }}',
            hasDraftAvailable: false,
            draftTimestamp: '',
            saveDraftTimer: null,

            saveDraftDebounced() {
                clearTimeout(this.saveDraftTimer);
                this.saveDraftTimer = setTimeout(() => {
                    try {
                        const payload = {
                            timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                            rawTime: Date.now(),
                            gridData: this.gridData
                        };
                        localStorage.setItem(this.draftKey, JSON.stringify(payload));
                    } catch (e) {}
                }, 1000);
            },

            checkDraft() {
                try {
                    const raw = localStorage.getItem(this.draftKey);
                    if (!raw) return;
                    const parsed = JSON.parse(raw);
                    if (parsed && parsed.gridData && (Date.now() - (parsed.rawTime || 0) < 7 * 24 * 60 * 60 * 1000)) {
                        this.hasDraftAvailable = true;
                        this.draftTimestamp = parsed.timestamp || 'sebelumnya';
                    }
                } catch (e) {}
            },

            restoreDraft() {
                try {
                    const raw = localStorage.getItem(this.draftKey);
                    if (!raw) return;
                    const parsed = JSON.parse(raw);
                    if (parsed && parsed.gridData) {
                        this.gridData = parsed.gridData;
                        this.isDirty = true;
                        this.hasDraftAvailable = false;
                        alert('Data draf berhasil dipulihkan ke formulir spreadsheet!');
                    }
                } catch (e) {
                    alert('Gagal memulihkan draf.');
                }
            },

            dismissDraft() {
                localStorage.removeItem(this.draftKey);
                this.hasDraftAvailable = false;
            },

            getNextHafalan(studentId, cellHafalans = []) {
                let validPrevious = (cellHafalans || []).filter(h => h.surah_id && h.ayah_end);
                if (validPrevious.length > 0) {
                    let lastH = validPrevious[validPrevious.length - 1];
                    let surahId = parseInt(lastH.surah_id);
                    let ayahEnd = parseInt(lastH.ayah_end);
                    let details = this.surahDetails[surahId];
                    let totalAyah = details ? details.totalAyah : 1;

                    if (ayahEnd < totalAyah) {
                        return {
                            id: null,
                            surah_id: surahId,
                            surah: details ? details.name : '',
                            ayah_start: ayahEnd + 1,
                            ayah_end: ayahEnd + 1,
                            type: 'ziyadah',
                            score: '',
                            grade: '',
                            status: 'passed',
                            notes: ''
                        };
                    } else {
                        let nextSurahId = surahId < 114 ? surahId + 1 : 1;
                        let nextDetails = this.surahDetails[nextSurahId];
                        return {
                            id: null,
                            surah_id: nextSurahId,
                            surah: nextDetails ? nextDetails.name : '',
                            ayah_start: 1,
                            ayah_end: 1,
                            type: 'ziyadah',
                            score: '',
                            grade: '',
                            status: 'passed',
                            notes: ''
                        };
                    }
                }

                let lastInfo = this.lastHafalanMap[studentId];
                if (lastInfo) {
                    let details = this.surahDetails[lastInfo.next_surah_id];
                    return {
                        id: null,
                        surah_id: lastInfo.next_surah_id,
                        surah: details ? details.name : '',
                        ayah_start: lastInfo.next_ayah_start,
                        ayah_end: lastInfo.next_ayah_start,
                        type: 'ziyadah',
                        score: '',
                        grade: '',
                        status: 'passed',
                        notes: ''
                    };
                }

                return {
                    id: null,
                    surah_id: '',
                    surah: '',
                    ayah_start: '',
                    ayah_end: '',
                    type: 'ziyadah',
                    score: '',
                    grade: '',
                    status: 'passed',
                    notes: ''
                };
            },

            autoMarkHadir(studentId, date) {
                this.isDirty = true;
                if (!studentId || !date) return;
                let cell = this.gridData[studentId]?.dates[date];
                if (cell && (!cell.attendance || cell.attendance === '')) {
                    cell.attendance = 'hadir';
                }
            },

            syncAyahLimits(hafalan, studentId = null, date = null) {
                if (studentId && date) {
                    this.autoMarkHadir(studentId, date);
                }
                if (!hafalan.surah_id) return;
                const details = this.surahDetails[hafalan.surah_id];
                if (details) {
                    hafalan.surah = details.name;
                    if (!hafalan.ayah_start) hafalan.ayah_start = 1;
                    hafalan.ayah_end = details.totalAyah;
                }
            },

            handleAttendanceChange(studentId, date, value) {
                this.isDirty = true;
                let cell = this.gridData[studentId].dates[date];
                cell.attendance = value;

                if (value === 'hadir') {
                    if (cell.hafalans.length === 1 && !cell.hafalans[0].surah_id) {
                        let autoNext = this.getNextHafalan(studentId, []);
                        if (autoNext.surah_id) {
                            cell.hafalans[0].surah_id = autoNext.surah_id;
                            cell.hafalans[0].surah = autoNext.surah;
                            cell.hafalans[0].ayah_start = autoNext.ayah_start;
                            cell.hafalans[0].ayah_end = autoNext.ayah_end;
                        }
                    }
                } else {
                    // Clear setoran fields if absent (sakit, izin, alpa)
                    cell.hafalans.forEach(h => {
                        h.surah_id = '';
                        h.surah = '';
                        h.ayah_start = '';
                        h.ayah_end = '';
                        h.score = '';
                        h.grade = '';
                    });
                }
            },

            jumpToToday() {
                if (this.dates.includes(this.todayDate)) {
                    this.scrollToColumn(this.todayDate);
                } else {
                    if (confirm('Tanggal hari ini (' + this.formatDateIndo(this.todayDate) + ') tidak ada dalam rentang filter saat ini. Tampilkan bulan berjalan?')) {
                        window.location.href = "{{ route('tahfidz-journals.spreadsheet') }}?classroom_id=" + this.selectedClass + "&month=" + this.currentMonth + "&week=all";
                    }
                }
            },

            scrollToColumn(dateStr) {
                this.selectedMobileDate = dateStr;
                this.$nextTick(() => {
                    const col = document.getElementById('col-header-' + dateStr);
                    if (col) {
                        col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                        col.classList.add('ring-2', 'ring-emerald-500');
                        setTimeout(() => col.classList.remove('ring-2', 'ring-emerald-500'), 1500);
                    }
                });
            },

            formatDateIndo(dateStr) {
                if (!dateStr) return '';
                const days = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                const parts = dateStr.split('-');
                if (parts.length !== 3) return dateStr;
                const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                const dayName = days[d.getDay()];
                const monthName = months[parseInt(parts[1])];
                return `${dayName}, ${parseInt(parts[2])} ${monthName} ${parts[0]}`;
            },

            submitForm() {
                if (this.isSaving) return;
                this.isSaving = true;
                this.isDirty = false;
                localStorage.removeItem(this.draftKey);

                this.$nextTick(() => {
                    const form = document.getElementById('spreadsheet-form');
                    if (form) {
                        form.submit();
                    } else {
                        this.isSaving = false;
                    }
                });
            }
        }));
    });
</script>

<div class="space-y-5" x-data="spreadsheetData">
    <!-- Top Mode Switcher Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('tahfidz-journals.spreadsheet', ['classroom_id' => $selectedClassroomId, 'month' => $selectedMonth]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Mode Spreadsheet (Matriks Cepat)</span>
            </a>

            <a href="{{ route('tahfidz-journals.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold border border-slate-200 transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Riwayat & Daftar Catatan</span>
            </a>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="jumpToToday()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition cursor-pointer active:scale-95">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Lompat ke Hari Ini</span>
            </button>
        </div>
    </div>

    <!-- FILTER & CONTROL PANEL -->
    <div class="bg-white border border-slate-200/90 shadow-xs rounded-2xl p-4 sm:p-5">
        <form method="GET" action="{{ route('tahfidz-journals.spreadsheet') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 items-end">
            <!-- Classroom Selector -->
            <div>
                <label for="classroom_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Kelas Halaqah
                </label>
                <select id="classroom_id" name="classroom_id" x-model="selectedClass" onchange="this.form.submit()"
                        class="block w-full rounded-xl border border-slate-300 bg-white text-xs sm:text-sm py-2 px-3 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-slate-800 font-semibold cursor-pointer shadow-xs">
                    @foreach ($classrooms as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassroomId == $class->id ? 'selected' : '' }}>
                            Kelas {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Month Selector -->
            <div>
                <label for="month" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Pilih Bulan & Tahun
                </label>
                <input type="month" id="month" name="month" x-model="selectedMonth" onchange="this.form.submit()"
                       class="block w-full rounded-xl border border-slate-300 bg-white text-xs sm:text-sm py-2 px-3 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-slate-800 font-semibold cursor-pointer shadow-xs">
            </div>

            <!-- Week Selector -->
            <div>
                <label for="week" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Pilih Pekan
                </label>
                <select id="week" name="week" x-model="selectedWeek" onchange="this.form.submit()"
                        class="block w-full rounded-xl border border-slate-300 bg-white text-xs sm:text-sm py-2 px-3 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-slate-800 font-semibold cursor-pointer shadow-xs">
                    <option value="all" {{ $selectedWeek === 'all' ? 'selected' : '' }}>Semua Pekan (Scroll)</option>
                    @foreach ($weeksList as $index => $w)
                        <option value="{{ $index }}" {{ $selectedWeek == $index ? 'selected' : '' }}>
                            {{ $w['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-bold shadow-xs transition cursor-pointer min-h-[38px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Tampilkan Kelas</span>
                </button>
            </div>
        </form>

        <!-- Active Dates Bar -->
        @if (count($dates) > 0)
        @php
            $todayDate = now()->format('Y-m-d');
        @endphp
        <div class="mt-4 pt-4 border-t border-slate-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                <div class="flex items-center gap-2 flex-wrap">
                    <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-700">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Tanggal Aktif ({{ $selectedWeek === 'all' ? 'Semua Pekan' : 'Pekan ' . $selectedWeek }}):</span>
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[11px] text-slate-500 hidden lg:inline">
                        Klik tanggal untuk memusatkan tampilan ke kolom hari tersebut
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach ($dates as $d)
                    @php
                        $cDate = \Carbon\Carbon::parse($d)->locale('id');
                        $dDayNum = $cDate->format('j');
                        $dDayName = $cDate->translatedFormat('D');
                        $dDayFullName = $cDate->translatedFormat('l');
                        $isToday = ($d === $todayDate);
                    @endphp
                    <button
                        type="button"
                        @click="scrollToColumn('{{ $d }}')"
                        :class="selectedMobileDate === '{{ $d }}' ? 'bg-emerald-600 text-white font-black ring-2 ring-emerald-500 scale-105 shadow-xs' : '{{ $isToday ? 'bg-emerald-50 text-emerald-800 font-bold border-2 border-emerald-400' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200' }}'"
                        class="px-2.5 py-1 rounded-xl text-xs transition cursor-pointer flex items-center gap-1 min-w-[42px] justify-center relative"
                        title="{{ $isToday ? 'Hari Ini (' . $dDayFullName . ', ' . $dDayNum . ' ' . $cDate->translatedFormat('M') . ')' : ($dDayFullName . ', ' . $cDate->translatedFormat('d F Y')) }}"
                    >
                        <span class="font-bold text-xs">{{ $dDayNum }}</span>
                        <span class="text-[9px] opacity-75 uppercase">{{ $dDayName }}</span>
                        @if ($isToday)
                            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-white" title="Hari Ini"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- DRAFT RECOVERY BANNER -->
    <div x-show="hasDraftAvailable"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="bg-amber-50 border border-amber-300 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
         style="display: none;">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <div>
                <h4 class="text-xs font-bold text-amber-900">Ditemukan Draf Belum Tersimpan</h4>
                <p class="text-[11px] text-amber-800">Ada perubahan data setoran dari sesi sebelumnya (<span x-text="draftTimestamp" class="font-bold"></span>) yang belum disimpan. Pulihkan data ini?</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="restoreDraft()"
                    class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Pulihkan Data Draf</span>
            </button>
            <button type="button" @click="dismissDraft()"
                    class="px-3.5 py-1.5 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold transition cursor-pointer">
                Abaikan
            </button>
        </div>
    </div>

    <!-- MAIN FORM & ACTIONS -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white border border-slate-200/90 p-3 sm:p-4 rounded-2xl shadow-xs">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span>Matriks Setoran Al-Qur'an Santri ({{ $students->count() }} Santri)</span>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="button" @click="submitForm()" :disabled="isSaving"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 disabled:bg-slate-400 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer gap-2 min-h-[40px]">
                <template x-if="!isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span>Simpan Perubahan Kelas</span>
                    </span>
                </template>
                <template x-if="isSaving">
                    <span class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Menyimpan Perubahan...</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    @if ($students->isEmpty())
        <div class="p-8 text-center bg-white border border-slate-200 rounded-2xl">
            <p class="text-sm text-slate-500">Tidak ada santri aktif di kelas halaqah terpilih.</p>
        </div>
    @else
        <!-- SPREADSHEET FORM -->
        <form id="spreadsheet-form" method="POST" action="{{ route('tahfidz-journals.batch-store') }}" @input="isDirty = true" @change="isDirty = true">
            @csrf
            <input type="hidden" name="classroom_id" :value="selectedClass">
            <input type="hidden" name="month" :value="selectedMonth">
            <input type="hidden" name="week" :value="selectedWeek">

            <!-- ========================================== -->
            <!-- DESKTOP / TABLET SPREADSHEET MATRIX VIEW   -->
            <!-- ========================================== -->
            <div class="hidden md:block isolate bg-white border border-slate-200/90 rounded-2xl overflow-x-auto overflow-y-auto touch-scroll max-h-[calc(100vh-14rem)] shadow-xs">
                <table class="min-w-full divide-y divide-slate-200 table-fixed border-collapse">
                    <thead class="sticky top-0 z-30 bg-slate-100 shadow-xs">
                        <tr>
                            <!-- Sticky Name Column Header -->
                            <th class="sticky top-0 left-0 z-40 bg-slate-100 px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider w-52 border-r-2 border-b border-slate-300 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)]">
                                Nama Santri
                            </th>
                            <!-- Date Headers -->
                            <template x-for="col in columns" :key="col.date">
                                <th
                                    :id="'col-header-' + col.date"
                                    class="sticky top-0 z-30 px-2.5 py-2 text-center text-xs font-bold uppercase tracking-wider w-64 border-r border-b transition-colors"
                                    :class="col.date === todayDate ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                >
                                    <div class="flex items-center gap-1.5 text-left">
                                        <span x-text="col.label" class="block"></span>
                                        <template x-if="col.date === todayDate">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-600 text-white shadow-xs tracking-normal">
                                                HARI INI
                                            </span>
                                        </template>
                                    </div>
                                    <span x-text="col.sub_label" class="block text-[10px] font-medium normal-case mt-0.5 text-left"
                                          :class="col.date === todayDate ? 'text-emerald-700 font-bold' : 'text-slate-400'"></span>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <template x-for="student in students" :key="student.id">
                            <tr class="group hover:bg-slate-50/60 transition-colors">
                                <!-- Sticky Name Column -->
                                <td class="sticky left-0 z-20 bg-white group-hover:bg-slate-50 px-4 py-3 border-r-2 border-b border-slate-300 font-bold text-xs text-slate-900 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)] transition-colors">
                                    <span x-text="student.name" class="block"></span>
                                    <span class="block text-[10px] text-slate-400 font-mono mt-0.5" x-text="'NIS: ' + (student.nis || '-')"></span>
                                </td>

                                <!-- Date Matrix Cells -->
                                <template x-for="date in dates" :key="date">
                                    <td
                                        class="p-2.5 border-r border-b border-slate-200 align-top transition-colors"
                                        :class="date === todayDate ? 'bg-emerald-50/30' : ''"
                                    >
                                        <div class="space-y-2" x-data="{ cell: gridData[student.id].dates[date] }">
                                            <!-- PRESENSI PILLS (ATAS) -->
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                                <div class="flex items-center gap-1 w-full justify-between">
                                                    <button type="button" @click="handleAttendanceChange(student.id, date, 'hadir')"
                                                            :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-transparent text-slate-400 border-slate-200 hover:bg-slate-100'"
                                                            class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded-sm cursor-pointer transition-colors w-8 text-center" title="Hadir">H</button>
                                                    <button type="button" @click="handleAttendanceChange(student.id, date, 'sakit')"
                                                            :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-transparent text-slate-400 border-slate-200 hover:bg-slate-100'"
                                                            class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded-sm cursor-pointer transition-colors w-8 text-center" title="Sakit">S</button>
                                                    <button type="button" @click="handleAttendanceChange(student.id, date, 'izin')"
                                                            :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white border-blue-500 font-bold' : 'bg-transparent text-slate-400 border-slate-200 hover:bg-slate-100'"
                                                            class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded-sm cursor-pointer transition-colors w-8 text-center" title="Izin">I</button>
                                                    <button type="button" @click="handleAttendanceChange(student.id, date, 'alpa')"
                                                            :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white border-rose-600 font-bold' : 'bg-transparent text-slate-400 border-slate-200 hover:bg-slate-100'"
                                                            class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded-sm cursor-pointer transition-colors w-8 text-center" title="Alpa">A</button>
                                                </div>
                                                <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][attendance]'" :value="cell.attendance" :disabled="isMobileView">
                                            </div>

                                            <!-- INPUT SETORAN FIELDS (LOCKED IF ABSENT) -->
                                            <div :class="(cell.attendance && cell.attendance !== 'hadir') ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-2">
                                                <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                    <div class="p-2 bg-slate-50/75 border border-slate-200 rounded-lg relative space-y-1.5">
                                                        <!-- Surah Select -->
                                                        <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'"
                                                                x-model="h.surah_id" @change="syncAyahLimits(h, student.id, date)"
                                                                :disabled="isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                                class="block w-full rounded-md border border-slate-300 bg-white text-[11px] px-2 py-1 text-slate-800 font-medium focus:ring-1 focus:ring-emerald-500">
                                                            <option value="">Pilih Surah</option>
                                                            @foreach ($surahs as $s)
                                                                <option value="{{ $s['number'] }}">{{ $s['number'] }}. {{ $s['name_latin'] }}</option>
                                                            @endforeach
                                                        </select>

                                                        <!-- Ayah Range -->
                                                        <div class="grid grid-cols-2 gap-1">
                                                            <input type="number" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_start]'"
                                                                   x-model.number="h.ayah_start" @input="autoMarkHadir(student.id, date)" placeholder="Awal"
                                                                   :disabled="isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                                   class="block w-full rounded-md border border-slate-300 bg-white text-[11px] px-1.5 py-0.5 font-mono text-center focus:ring-1 focus:ring-emerald-500">
                                                            <input type="number" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_end]'"
                                                                   x-model.number="h.ayah_end" @input="autoMarkHadir(student.id, date)" placeholder="Akhir"
                                                                   :disabled="isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                                   class="block w-full rounded-md border border-slate-300 bg-white text-[11px] px-1.5 py-0.5 font-mono text-center focus:ring-1 focus:ring-emerald-500">
                                                        </div>

                                                        <!-- Type & Grade -->
                                                        <div class="grid grid-cols-2 gap-1">
                                                            <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][type]'"
                                                                    x-model="h.type" :disabled="isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                                    class="block w-full rounded-md border border-slate-300 bg-white text-[10px] px-1 py-0.5 text-slate-700 font-medium">
                                                                <option value="ziyadah">Ziyadah</option>
                                                                <option value="murajaah">Muraja'ah</option>
                                                                <option value="tahsin">Tahsin</option>
                                                                <option value="tilawah">Tilawah</option>
                                                                <option value="tasmi">Tasmi'</option>
                                                            </select>
                                                            <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][grade]'"
                                                                    x-model="h.grade" :disabled="isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                                    class="block w-full rounded-md border border-slate-300 bg-white text-[10px] px-1 py-0.5 text-slate-700 font-medium">
                                                                <option value="">Nilai</option>
                                                                <option value="Mumtaz (A)">Mumtaz (A)</option>
                                                                <option value="Jayyid Jiddan (B+)">Jayyid Jiddan (B+)</option>
                                                                <option value="Jayyid (B)">Jayyid (B)</option>
                                                                <option value="Maqbul (C)">Maqbul (C)</option>
                                                                <option value="Rasib (D)">Rasib (D)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Hidden Inputs -->
                                                        <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                        <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah]'" :value="h.surah">
                                                        <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][status]'" :value="h.status">

                                                        <!-- Remove Button -->
                                                        <template x-if="cell.hafalans.length > 1">
                                                            <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)"
                                                                    class="absolute -top-1.5 -right-1.5 bg-rose-100 hover:bg-rose-200 text-rose-700 rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold border border-rose-300 cursor-pointer">
                                                                &times;
                                                            </button>
                                                        </template>
                                                    </div>
                                                </template>

                                                <!-- Add Surah Button -->
                                                <button type="button" @click="isDirty = true; cell.hafalans.push(getNextHafalan(student.id, cell.hafalans))"
                                                        :disabled="cell.attendance !== 'hadir'"
                                                        class="w-full inline-flex items-center justify-center py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-md text-[10px] font-bold border border-emerald-200 transition cursor-pointer">
                                                    + Tambah Surat
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- ========================================== -->
            <!-- MOBILE VIEW (Layar HP / Responsif)         -->
            <!-- ========================================== -->
            <div class="md:hidden space-y-4">
                <template x-for="student in students" :key="student.id + '-' + selectedMobileDate">
                    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs space-y-3" x-data="{ get cell() { return gridData[student.id].dates[selectedMobileDate] } }">
                        <!-- Student Header -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <div>
                                <h4 class="font-bold text-sm text-slate-900" x-text="student.name"></h4>
                                <p class="text-[10px] text-slate-400 font-mono" x-text="'NIS: ' + (student.nis || '-')"></p>
                            </div>
                            <span class="px-2 py-0.5 rounded-sm bg-emerald-50 text-emerald-700 font-bold text-[10px]" x-text="formatDateIndo(selectedMobileDate)"></span>
                        </div>

                        <!-- Attendance Selection -->
                        <div class="space-y-1">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Kehadiran</span>
                            <div class="grid grid-cols-4 gap-1.5">
                                <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'hadir')"
                                        :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-50 text-slate-600 border border-slate-200'"
                                        class="py-1.5 text-xs rounded-lg cursor-pointer text-center transition">Hadir</button>
                                <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'sakit')"
                                        :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white font-bold' : 'bg-slate-50 text-slate-600 border border-slate-200'"
                                        class="py-1.5 text-xs rounded-lg cursor-pointer text-center transition">Sakit</button>
                                <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'izin')"
                                        :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white font-bold' : 'bg-slate-50 text-slate-600 border border-slate-200'"
                                        class="py-1.5 text-xs rounded-lg cursor-pointer text-center transition">Izin</button>
                                <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'alpa')"
                                        :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white font-bold' : 'bg-slate-50 text-slate-600 border border-slate-200'"
                                        class="py-1.5 text-xs rounded-lg cursor-pointer text-center transition">Alpa</button>
                            </div>
                            <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][attendance]'" :value="cell.attendance" :disabled="!isMobileView">
                        </div>

                        <!-- Setoran Inputs (Mobile) -->
                        <div :class="(cell.attendance && cell.attendance !== 'hadir') ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-3">
                            <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 relative space-y-2.5">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Surah</label>
                                        <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'"
                                                x-model="h.surah_id" @change="syncAyahLimits(h, student.id, selectedMobileDate)"
                                                :disabled="!isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                class="block w-full rounded-md border border-slate-300 bg-white text-xs py-1.5 px-2 font-medium text-slate-800">
                                            <option value="">Pilih Surah</option>
                                            @foreach ($surahs as $s)
                                                <option value="{{ $s['number'] }}">{{ $s['number'] }}. {{ $s['name_latin'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ayat Mulai</label>
                                            <input type="number" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_start]'"
                                                   x-model.number="h.ayah_start" @input="autoMarkHadir(student.id, selectedMobileDate)" placeholder="Awal"
                                                   :disabled="!isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                   class="block w-full rounded-md border border-slate-300 bg-white text-xs py-1.5 px-2 font-mono text-center">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Ayat Akhir</label>
                                            <input type="number" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_end]'"
                                                   x-model.number="h.ayah_end" @input="autoMarkHadir(student.id, selectedMobileDate)" placeholder="Akhir"
                                                   :disabled="!isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                   class="block w-full rounded-md border border-slate-300 bg-white text-xs py-1.5 px-2 font-mono text-center">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jenis Setoran</label>
                                            <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][type]'"
                                                    x-model="h.type" :disabled="!isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                    class="block w-full rounded-md border border-slate-300 bg-white text-xs py-1.5 px-2 font-medium">
                                                <option value="ziyadah">Ziyadah</option>
                                                <option value="murajaah">Muraja'ah</option>
                                                <option value="tahsin">Tahsin</option>
                                                <option value="tilawah">Tilawah</option>
                                                <option value="tasmi">Tasmi'</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nilai</label>
                                            <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][grade]'"
                                                    x-model="h.grade" :disabled="!isMobileView || (cell.attendance && cell.attendance !== 'hadir')"
                                                    class="block w-full rounded-md border border-slate-300 bg-white text-xs py-1.5 px-2 font-medium">
                                                <option value="">Pilih Nilai</option>
                                                <option value="Mumtaz (A)">Mumtaz (A)</option>
                                                <option value="Jayyid Jiddan (B+)">Jayyid Jiddan (B+)</option>
                                                <option value="Jayyid (B)">Jayyid (B)</option>
                                                <option value="Maqbul (C)">Maqbul (C)</option>
                                                <option value="Rasib (D)">Rasib (D)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Hidden Inputs -->
                                    <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                    <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah]'" :value="h.surah">
                                    <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][status]'" :value="h.status">

                                    <!-- Delete Button -->
                                    <template x-if="cell.hafalans.length > 1">
                                        <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)"
                                                class="absolute top-2 right-2 text-rose-600 text-xs font-bold bg-white border border-slate-200 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-xs">
                                            &times;
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <button type="button" @click="isDirty = true; cell.hafalans.push(getNextHafalan(student.id, cell.hafalans))"
                                    :disabled="cell.attendance !== 'hadir'"
                                    class="w-full py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                + Tambah Surat Setoran
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </form>
    @endif

</div>
@endsection
