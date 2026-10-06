@extends('layouts.app', [
    'header' => 'Monitoring & Analitik Lembaga',
    'subheader' => 'Pemantauan capaian target kurikulum tahfidz kelas dan deteksi dini santri butuh bimbingan'
])

@section('content')
<div class="space-y-8" x-data="{
    targetModal: {
        isOpen: false,
        classroomId: '',
        className: '',
        targetJuz: 30,
        targetDescription: ''
    },
    openTargetModal(id, name, currentJuz, currentDesc) {
        this.targetModal.classroomId = id;
        this.targetModal.className = name;
        this.targetModal.targetJuz = currentJuz;
        this.targetModal.targetDescription = currentDesc || '';
        this.targetModal.isOpen = true;
    }
}">

    <!-- Top Summary Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Overall Progress -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Capaian Kurikulum</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $summaryStats['overall_curriculum_progress'] }}%</span>
                    <span class="text-xs font-semibold text-emerald-600">Seluruh Kelas</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Dari {{ $summaryStats['total_classes'] }} rombel SD/MI</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>

        <!-- Card 2: Completed Targets -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Santri Tuntas Target</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $summaryStats['total_completed_target'] }}</span>
                    <span class="text-xs font-semibold text-emerald-600">Santri (100%)</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Khatam target juz kelas</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>

        <!-- Card 3: Early Warning Alert -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-rose-500">Early Warning (Butuh Bimbingan)</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-rose-600">{{ $summaryStats['total_warning_students'] }}</span>
                    <span class="text-xs font-semibold text-rose-600">Santri Terdeteksi</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">{{ $summaryStats['critical_count'] }} Kritis &bull; {{ $summaryStats['warning_count'] }} Perlu Perhatian</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>

        <!-- Card 4: Total Santri Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Santri Aktif</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $summaryStats['total_students'] }}</span>
                    <span class="text-xs font-semibold text-slate-500">Santri</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Terdaftar dalam halaqah</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- SECTION 1: PROGRESS BAR TARGET KURIKULUM PER KELAS -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Progress Capaian Target Kurikulum per Kelas</h3>
                <p class="text-xs text-slate-500">Akumulasi persentase pencapaian target hafalan santri pada setiap jenjang kelas</p>
            </div>
            <span class="text-xs text-slate-500 font-medium bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 self-start sm:self-auto">
                Jenjang SD/MI (Kelas 1 - Kelas 6)
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($classAnalytics as $ca)
                @php
                    $cls = $ca['classroom'];
                    $pct = $ca['average_percentage'];
                    $progressColor = $pct >= 80 ? 'from-emerald-500 to-teal-600' : ($pct >= 50 ? 'from-teal-500 to-blue-500' : 'from-amber-500 to-emerald-500');
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4 hover:border-emerald-300 transition group flex flex-col justify-between">
                    <!-- Top Card Header -->
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="text-base font-black text-slate-900 group-hover:text-emerald-700 transition">
                                    Kelas {{ $cls->name }}
                                </h4>
                                <div class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 rounded-sm text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span>Target: {{ $ca['target_label'] }}</span>
                                </div>
                            </div>

                            @if(auth()->user()->isAdmin())
                                <button type="button"
                                        @click="openTargetModal('{{ $cls->id }}', '{{ $cls->name }}', {{ $ca['target_juz'] }}, '{{ addslashes($cls->target_description ?? '') }}')"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                        title="Ubah Target Kurikulum">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <!-- Progress Bar Display -->
                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-600">Rata-rata Capaian</span>
                                <span class="text-slate-900 font-black text-sm">{{ $pct }}%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200">
                                <div class="h-full rounded-full bg-linear-to-r {{ $progressColor }} transition-all duration-500"
                                     style="width: {{ min(100, max(4, $pct)) }}%"></div>
                            </div>
                        </div>

                        <!-- Class Composition Stats -->
                        <div class="grid grid-cols-3 gap-2 mt-4 pt-3 border-t border-slate-100 text-center">
                            <div class="p-2 rounded-xl bg-slate-50">
                                <p class="text-[10px] uppercase font-bold text-slate-400">Total Santri</p>
                                <p class="text-sm font-black text-slate-800 mt-0.5">{{ $ca['total_students'] }}</p>
                            </div>
                            <div class="p-2 rounded-xl bg-emerald-50/60">
                                <p class="text-[10px] uppercase font-bold text-emerald-700">Tuntas</p>
                                <p class="text-sm font-black text-emerald-700 mt-0.5">{{ $ca['completed_students'] }}</p>
                            </div>
                            <div class="p-2 rounded-xl bg-blue-50/60">
                                <p class="text-[10px] uppercase font-bold text-blue-700">Berproses</p>
                                <p class="text-sm font-black text-blue-700 mt-0.5">{{ $ca['in_progress_students'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Top Achievers Preview -->
                    @if (!empty($ca['top_achievers']))
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-[11px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider">Top Capaian Tertinggi:</p>
                            <div class="space-y-1">
                                @foreach ($ca['top_achievers'] as $top)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-700 truncate max-w-[170px] font-medium">{{ $top['student']->name }}</span>
                                        <span class="font-bold {{ $top['percentage'] >= 100 ? 'text-emerald-700' : 'text-slate-600' }}">
                                            {{ $top['percentage'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- SECTION 2: EARLY WARNING SYSTEM (SANTRI BUTUH BIMBINGAN) -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <!-- Section Header & Filter Toolbar -->
        <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <h3 class="text-base font-bold text-slate-900">Early Warning System: Santri Butuh Bimbingan Khusus</h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Deteksi otomatis santri dengan kendala hafalan (jarang setor ziyadah, status sering diulang/remidi, atau absensi)
                </p>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap items-center gap-2 self-start md:self-auto">
                <select name="classroom_id" onchange="this.form.submit()"
                        class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white">
                    <option value="">Semua Kelas</option>
                    @foreach ($classrooms as $room)
                        <option value="{{ $room->id }}" {{ $selectedClassroomId == $room->id ? 'selected' : '' }}>
                            Kelas {{ $room->name }}
                        </option>
                    @endforeach
                </select>

                <select name="risk_level" onchange="this.form.submit()"
                        class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white">
                    <option value="">Semua Level Risiko</option>
                    <option value="critical" {{ $selectedRiskLevel === 'critical' ? 'selected' : '' }}>Kritis Saja</option>
                    <option value="warning" {{ $selectedRiskLevel === 'warning' ? 'selected' : '' }}>Perlu Perhatian Saja</option>
                    <option value="monitoring" {{ $selectedRiskLevel === 'monitoring' ? 'selected' : '' }}>Dalam Pantauan</option>
                </select>

                @if($selectedClassroomId || $selectedRiskLevel)
                    <a href="{{ route('analytics.index') }}" class="text-xs text-rose-600 hover:underline px-1">Reset</a>
                @endif
            </form>
        </div>

        <!-- Warning Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Santri & Kelas</th>
                        <th class="px-6 py-3.5">Status Risiko</th>
                        <th class="px-6 py-3.5">Diagnosa Kendala</th>
                        <th class="px-6 py-3.5">Setoran Terakhir</th>
                        <th class="px-6 py-3.5 text-right">Tindakan Pendampingan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($earlyWarningStudents as $item)
                        @php
                            $st = $item['student'];
                            $riskBadge = match ($item['risk_level']) {
                                'critical' => 'bg-rose-100 text-rose-800 border-rose-300',
                                'warning' => 'bg-amber-100 text-amber-800 border-amber-300',
                                default => 'bg-blue-100 text-blue-800 border-blue-300',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition text-xs">
                            <!-- Student Info -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $st->name }}</div>
                                <div class="text-[11px] text-slate-400">
                                    NIS: {{ $st->nis }} &bull; Kelas {{ $st->classroom->name ?? '-' }}
                                </div>
                            </td>

                            <!-- Risk Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $riskBadge }}">
                                    {{ $item['risk_label'] }}
                                </span>
                            </td>

                            <!-- Reasons / Diagnosa -->
                            <td class="px-6 py-4 max-w-sm">
                                <ul class="space-y-1">
                                    @foreach ($item['reasons'] as $r)
                                        <li class="flex items-start gap-1.5 text-slate-700">
                                            <span class="text-rose-500 font-black mt-0.5">&bull;</span>
                                            <span>{{ $r }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>

                            <!-- Last Setoran -->
                            <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                @if ($item['latest_journal'])
                                    <div class="font-semibold">
                                        {{ \Carbon\Carbon::parse($item['latest_journal']->date)->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Surat {{ $item['latest_journal']->surah ?: 'Juz ' . $item['latest_journal']->juz }} ({{ $item['latest_journal']->grade ?: 'Selesai' }})
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">Belum pernah setor</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($item['wa_link'])
                                        <a href="{{ $item['wa_link'] }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                                           title="Kirim pesan WhatsApp ke Wali Santri">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                            <span>Hubungi Wali (WA)</span>
                                        </a>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">No. WA belum diatur</span>
                                    @endif

                                    <a href="{{ route('tahfidz-journals.index', ['student_id' => $st->id]) }}"
                                       class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition"
                                       title="Buka Catatan Jurnal Lengkap">
                                        Riwayat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-8 h-8 mx-auto text-emerald-500 mb-2 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="font-bold text-slate-700 text-sm">Alhamdulillah, Tidak Ada Santri Berisiko Kritis</p>
                                <p class="text-xs text-slate-500 mt-0.5">Semua santri aktif menyetor dan mengikuti halaqah tahfizh dengan baik.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL EDIT TARGET KURIKULUM KELAS -->
    <div x-show="targetModal.isOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.away="targetModal.isOpen = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full p-6 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h4 class="font-bold text-base text-slate-900">
                    Sesuaikan Target Kurikulum Kelas <span x-text="targetModal.className"></span>
                </h4>
                <button type="button" @click="targetModal.isOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('analytics.update-target') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="classroom_id" :value="targetModal.classroomId">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Target Juz (1 - 30)
                    </label>
                    <input type="number" name="target_juz" min="1" max="30" x-model="targetModal.targetJuz" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    <p class="text-[11px] text-slate-400 mt-1">Standar SD/MI: Kelas 1 (Juz 30), Kelas 2 (Juz 29), Kelas 3 (Juz 28), Kelas 4 (Juz 1), dst.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Keterangan Target (Opsional)
                    </label>
                    <input type="text" name="target_description" x-model="targetModal.targetDescription"
                           placeholder="Contoh: Juz 30 (An-Naba s.d. An-Nas)"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 outline-hidden">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="targetModal.isOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition">
                        Simpan Target
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

