@extends('layouts.app', [
    'header' => 'Portal Wali Santri',
    'subheader' => 'Pantau perkembangan hafalan, adab, akademik, dan administrasi ananda secara berkala'
])

@section('content')
<div class="space-y-8" x-data="{
    activeTab: 'tahfidz',
    trends: {{ json_encode($trends ?? ['has_data' => false]) }},
    initCharts() {
        if (!this.trends || !this.trends.has_data) return;

        const labels = this.trends.labels;

        // 1. Tahfidz Chart
        const ctxTahfidz = document.getElementById('parentTahfidzChart');
        if (ctxTahfidz) {
            new Chart(ctxTahfidz, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Akumulasi Capaian Juz',
                        data: this.trends.tahfidz.juz,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 30,
                            ticks: {
                                stepSize: 5,
                                callback: function(value) { return value + ' Juz'; }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Capaian: ' + context.parsed.y + ' Juz';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Adab & Character Chart
        const ctxAdab = document.getElementById('parentAdabChart');
        if (ctxAdab) {
            new Chart(ctxAdab, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Rata-rata Gabungan',
                            data: this.trends.adab.average,
                            borderColor: '#0f172a',
                            borderWidth: 3,
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Ibadah',
                            data: this.trends.adab.ibadah,
                            borderColor: '#059669',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Akhlak',
                            data: this.trends.adab.akhlak,
                            borderColor: '#2563eb',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Kerapian',
                            data: this.trends.adab.kerapian,
                            borderColor: '#d97706',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Disiplin',
                            data: this.trends.adab.disiplin,
                            borderColor: '#7c3aed',
                            tension: 0.3,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 1,
                            max: 4,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    const map = { 4: 'A (Sangat Baik)', 3: 'B (Baik)', 2: 'C (Cukup)', 1: 'D (Kurang)' };
                                    return map[value] || value;
                                }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const map = { 4: 'A (Sangat Baik)', 3: 'B (Baik)', 2: 'C (Cukup)', 1: 'D (Kurang)' };
                                    const val = context.parsed.y;
                                    return ' ' + context.dataset.label + ': ' + (map[val] || val);
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Physical Growth Chart (TB & BB)
        const ctxPhysical = document.getElementById('parentPhysicalChart');
        if (ctxPhysical) {
            new Chart(ctxPhysical, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tinggi Badan (cm)',
                            data: this.trends.physical.height,
                            borderColor: '#0284c7',
                            backgroundColor: 'rgba(2, 132, 199, 0.08)',
                            yAxisID: 'yHeight',
                            tension: 0.3,
                            pointRadius: 5
                        },
                        {
                            label: 'Berat Badan (kg)',
                            data: this.trends.physical.weight,
                            borderColor: '#e11d48',
                            backgroundColor: 'rgba(225, 29, 72, 0.08)',
                            yAxisID: 'yWeight',
                            tension: 0.3,
                            pointRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yHeight: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Tinggi (cm)', font: { size: 10 } },
                            grid: { color: '#f1f5f9' }
                        },
                        yWeight: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Berat (kg)', font: { size: 10 } },
                            grid: { drawOnChartArea: false }
                        },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }
    }
}" x-init="$nextTick(() => initCharts())">

    @if(!$student)
        <div class="bg-white p-8 rounded-2xl border border-slate-200 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 mx-auto flex items-center justify-center font-bold text-xl">!</div>
            <h3 class="text-base font-bold text-slate-800">Akun Belum Terhubung dengan Data Santri</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Silakan hubungi administrator pesantren untuk menautkan akun Anda dengan nomor induk santri (NIS) ananda.
            </p>
        </div>
    @else
        @if($children->count() > 1)
            <!-- Child Switcher (parents with several children) -->
            <nav aria-label="Pilih ananda" class="-mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto">
                <div class="flex gap-2 w-max">
                    @foreach($children as $child)
                        <a href="{{ route('parent.dashboard', ['student' => $child->id]) }}"
                           @if($child->is($student)) aria-current="page" @endif
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border transition whitespace-nowrap {{ $child->is($student) ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs' : 'bg-white border-slate-200 text-slate-600 hover:border-emerald-400 hover:text-emerald-700' }}">
                            <span>{{ $child->name }}</span>
                            <span class="{{ $child->is($student) ? 'text-emerald-100' : 'text-slate-400' }} font-normal">Kelas {{ $child->classroom->name ?? '-' }}</span>
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        <!-- Student Profile Card -->
        <div class="p-6 rounded-2xl bg-linear-to-r from-emerald-800 to-teal-900 text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-700/60 text-emerald-200 border border-emerald-500/30 mb-2">
                    Kelas {{ $student->classroom->name ?? '-' }} &bull; {{ $student->gender == 'L' ? 'Ikhwan' : 'Akhwat' }}
                </span>
                <h2 class="text-2xl font-bold">{{ $student->name }}</h2>
                <p class="text-emerald-100/80 text-xs mt-1 font-mono">Nomor Induk Santri (NIS): {{ $student->nis }}</p>
            </div>

            @if($latestReport)
                <a href="{{ route('parent.reports.preview', $latestReport->id) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-emerald-50 text-emerald-900 rounded-xl text-xs font-bold shadow-md transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span>Download Rapor Terakhir ({{ $latestReport->period_title }})</span>
                </a>
            @endif
        </div>

        <!-- Latest Report Highlight -->
        @if($latestReport)
            @php $rec = $latestReport->record; @endphp
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900">Ringkasan Capaian Terkini ({{ $latestReport->period_title }})</h3>
                    <span class="text-xs text-slate-500">Tanggal Terbit: {{ \Carbon\Carbon::parse($latestReport->report_date)->translatedFormat('d F Y') }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Tahfidz -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                        <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            A. Tahfidz
                        </div>
                        <div class="text-xs text-slate-600 space-y-1 pt-1">
                            <div><span class="text-slate-400">Setoran:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('tahfidz_setoran') ?? '-' }}</span></div>
                            <div><span class="text-slate-400">Akumulasi:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('tahfidz_akumulasi') ?? '-' }}</span></div>
                            <div><span class="text-slate-400">Rincian Juz:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('tahfidz_rincian_juz') ?? '-' }}</span></div>
                        </div>
                    </div>

                    <!-- Kesantrian -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                        <div class="flex items-center gap-2 text-blue-700 font-bold text-xs uppercase tracking-wider">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            B. Kesantrian
                        </div>
                        <div class="text-xs text-slate-600 space-y-1 pt-1">
                            <div><span class="text-slate-400">Ibadah & Akhlak:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('adab_ibadah') ?? '-' }} / {{ $rec?->getFieldValue('adab_akhlak') ?? '-' }}</span></div>
                            <div><span class="text-slate-400">Fisik (TB / BB):</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('body_height_cm') ? $rec->getFieldValue('body_height_cm').'cm' : '-' }} / {{ $rec?->getFieldValue('body_weight_kg') ? $rec->getFieldValue('body_weight_kg').'kg' : '-' }}</span></div>
                            <div><span class="text-slate-400">Status Baligh:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('is_baligh') ?? '-' }}</span></div>
                        </div>
                    </div>

                    <!-- Akademik -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                        <div class="flex items-center gap-2 text-amber-700 font-bold text-xs uppercase tracking-wider">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            C. Akademik
                        </div>
                        <p class="text-xs text-slate-700 italic pt-1 line-clamp-3">
                            {{ $rec?->getFieldValue('academic_notes') ?: 'Mengikuti KBM di kelas dengan baik.' }}
                        </p>
                    </div>

                    <!-- Administrasi -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-2">
                        <div class="flex items-center gap-2 text-purple-700 font-bold text-xs uppercase tracking-wider">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            D. Administrasi
                        </div>
                        <div class="text-xs text-slate-600 space-y-1 pt-1">
                            <div><span class="text-slate-400">SPP Terakhir:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('last_spp') ?? '-' }}</span></div>
                            <div><span class="text-slate-400">Laundry:</span> <span class="font-semibold text-slate-800">{{ $rec?->getFieldValue('last_laundry') ?? '-' }}</span></div>
                            <div><span class="text-slate-400">Daftar Ulang:</span> <span class="font-semibold text-emerald-700">{{ $rec?->getFieldValue('registration_status') ?? '-' }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- SECTION: GRAFIK TREN PERKEMBANGAN ANANDA -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span>Grafik Tren Perkembangan Ananda</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau progres hafalan Al-Qur'an, evaluasi adab/karakter, dan kurva pertumbuhan fisik dari bulan ke bulan.
                    </p>
                </div>

                <!-- Tab Switcher -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                    <button type="button" @click="activeTab = 'tahfidz'"
                            :class="activeTab === 'tahfidz' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-lg text-xs transition">
                        Progres Tahfidz
                    </button>
                    <button type="button" @click="activeTab = 'adab'"
                            :class="activeTab === 'adab' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-lg text-xs transition">
                        Skor Adab & Karakter
                    </button>
                    <button type="button" @click="activeTab = 'physical'"
                            :class="activeTab === 'physical' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="px-3 py-1.5 rounded-lg text-xs transition">
                        Pertumbuhan Fisik
                    </button>
                </div>
            </div>

            @if(!$trends || !$trends['has_data'])
                <div class="py-10 text-center text-slate-400 space-y-2">
                    <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="font-bold text-slate-600 text-sm">Belum Ada Riwayat Laporan Terbit</p>
                    <p class="text-xs text-slate-400">Grafik perkembangan ananda akan otomatis muncul setelah rapor bulanan diterbitkan oleh pihak pesantren.</p>
                </div>
            @else
                <!-- Tab 1: Tahfidz Chart -->
                <div x-show="activeTab === 'tahfidz'" class="space-y-3">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Target Kurikulum: <strong class="text-emerald-700">30 Juz Al-Qur'an</strong></span>
                        <span class="text-[11px] text-slate-400">Satuan: Akumulasi Juz</span>
                    </div>
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="parentTahfidzChart"></canvas>
                    </div>
                </div>

                <!-- Tab 2: Adab & Character Chart -->
                <div x-show="activeTab === 'adab'" class="space-y-3" style="display: none;">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Skala Penilaian: <strong>A = 4.0</strong> (Sangat Baik), <strong>B = 3.0</strong> (Baik), <strong>C = 2.0</strong> (Cukup), <strong>D = 1.0</strong> (Kurang)</span>
                        <span class="text-[11px] text-slate-400">4 Aspek Karakter Kesantrian</span>
                    </div>
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="parentAdabChart"></canvas>
                    </div>
                </div>

                <!-- Tab 3: Physical Growth Chart -->
                <div x-show="activeTab === 'physical'" class="space-y-3" style="display: none;">
                    <div class="flex items-center justify-between text-xs text-slate-500">
                        <span>Sumbu Kiri: <strong>Tinggi Badan (cm)</strong> &bull; Sumbu Kanan: <strong>Berat Badan (kg)</strong></span>
                        <span class="text-[11px] text-slate-400">Pemantauan Kesehatan Santri</span>
                    </div>
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="parentPhysicalChart"></canvas>
                    </div>
                </div>
            @endif
        </div>

        <!-- All Reports History Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900">Arsip Riwayat Laporan Bulanan</h3>
                <span class="text-xs text-slate-500">{{ $reports->count() }} Dokumen Terbit</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Periode Laporan</th>
                            <th class="px-6 py-3.5">Tanggal Terbit</th>
                            <th class="px-6 py-3.5">Cut-off Keuangan</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reports as $rep)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-semibold text-slate-900">
                                    {{ $rep->period_title }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ \Carbon\Carbon::parse($rep->report_date)->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ \Carbon\Carbon::parse($rep->cutoff_date)->translatedFormat('d F Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('parent.reports.preview', $rep->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg text-xs font-semibold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Buka & Unduh PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                    Belum ada rapor yang dipublikasikan oleh pihak pesantren.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
