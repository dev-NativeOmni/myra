@extends('layouts.app', [
    'header' => 'Profil & Tren Progres Santri',
    'subheader' => $student->name . ' (NIS: ' . $student->nis . ') - Kelas ' . ($student->classroom->name ?? '-')
])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'tahfidz',
    trends: {{ json_encode($trends) }},
    initCharts() {
        if (!this.trends.has_data) return;

        const labels = this.trends.labels;

        // 1. Tahfidz Chart
        const ctxTahfidz = document.getElementById('studentTahfidzChart');
        if (ctxTahfidz) {
            new Chart(ctxTahfidz, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Akumulasi Capaian Juz',
                        data: this.trends.tahfidz.juz,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
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
                                callback: function(value) {
                                    return value + ' Juz';
                                }
                            },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
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
        const ctxAdab = document.getElementById('studentAdabChart');
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
                            backgroundColor: '#059669',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Akhlak',
                            data: this.trends.adab.akhlak,
                            borderColor: '#2563eb',
                            backgroundColor: '#2563eb',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Kerapian',
                            data: this.trends.adab.kerapian,
                            borderColor: '#d97706',
                            backgroundColor: '#d97706',
                            tension: 0.3,
                            pointRadius: 4
                        },
                        {
                            label: 'Disiplin',
                            data: this.trends.adab.disiplin,
                            borderColor: '#7c3aed',
                            backgroundColor: '#7c3aed',
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
                        x: {
                            grid: { display: false }
                        }
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
        const ctxPhysical = document.getElementById('studentPhysicalChart');
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
                        x: {
                            grid: { display: false }
                        }
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

    <!-- Top Navigation Bar & Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <a href="{{ route('students.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Master Data Santri</span>
        </a>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                <a href="{{ route('students.edit', $student->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Edit Data Santri</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Student Bio Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-extrabold text-xl shadow-md shadow-emerald-600/20 shrink-0">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xl font-black text-slate-900">{{ $student->name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $student->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                            {{ $student->is_active ? 'Santri Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-500 mt-1 flex-wrap">
                        <span class="font-mono font-medium">NIS: {{ $student->nis }}</span>
                        <span>&bull;</span>
                        <span>Kelas: <strong class="text-slate-700">{{ $student->classroom->name ?? '-' }}</strong></span>
                        <span>&bull;</span>
                        <span>Jenis Kelamin: <strong class="text-slate-700">{{ $student->gender == 'L' ? 'Laki-laki (Ikhwan)' : 'Perempuan (Akhwat)' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100 md:text-right">
                <span class="text-slate-400 block mb-0.5">Akun Wali Santri Terhubung:</span>
                @if($student->parentUser)
                    <span class="font-bold text-slate-800">{{ $student->parentUser->name }}</span>
                    <span class="block text-[11px] text-slate-400 font-mono">{{ $student->parentUser->email }}</span>
                @else
                    <span class="text-slate-400 italic">Belum ditautkan</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Tahfidz -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Akumulasi Tahfidz</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $trends['tahfidz']['latest'] ? $trends['tahfidz']['latest'] . ' Juz' : '-' }}</span>
                </div>
                <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Capaian Terkini</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
        </div>

        <!-- Stat 2: Adab Score -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Indeks Adab & Karakter</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $trends['adab']['latest_avg'] ? $trends['adab']['latest_avg'] . ' / 4.0' : '-' }}</span>
                </div>
                <p class="text-[11px] text-blue-600 font-medium mt-0.5">Rata-rata 4 Aspek Kesantrian</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>

        <!-- Stat 3: Physical Growth -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pertumbuhan Fisik</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-lg font-black text-slate-800">
                        {{ $trends['physical']['latest_height'] ? $trends['physical']['latest_height'] . ' cm' : '-' }} /
                        {{ $trends['physical']['latest_weight'] ? $trends['physical']['latest_weight'] . ' kg' : '-' }}
                    </span>
                </div>
                <p class="text-[11px] text-amber-600 font-medium mt-0.5">
                    BMI: {{ $trends['physical']['latest_bmi'] ?? '-' }}
                </p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>

        <!-- Stat 4: Reports Count -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Rapor Terbit</p>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-2xl font-black text-slate-800">{{ $reports->count() }}</span>
                    <span class="text-xs text-slate-500 font-semibold">Periode</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Riwayat Tercatat</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- SECTION: GRAFIK TREN PROGRES BULANAN -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span>Grafik Tren Progres Bulanan Santri</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Visualisasi dinamika capaian tahfidz, adab/karakter, dan pertumbuhan fisik santri antar periode.
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

        @if(!$trends['has_data'])
            <div class="py-12 text-center text-slate-400 space-y-2">
                <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-bold text-slate-600 text-sm">Belum Ada Riwayat Laporan Bulanan</p>
                <p class="text-xs text-slate-400">Grafik tren akan otomatis terbentuk setelah laporan bulanan diterbitkan untuk santri ini.</p>
            </div>
        @else
            <!-- Tab 1: Tahfidz Chart -->
            <div x-show="activeTab === 'tahfidz'" class="space-y-3">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Target Standar Kurikulum: <strong class="text-emerald-700">30 Juz Al-Qur'an</strong></span>
                    <span class="text-[11px] text-slate-400">Satuan: Akumulasi Juz</span>
                </div>
                <div class="h-64 sm:h-72 w-full">
                    <canvas id="studentTahfidzChart"></canvas>
                </div>
            </div>

            <!-- Tab 2: Adab & Character Chart -->
            <div x-show="activeTab === 'adab'" class="space-y-3" style="display: none;">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Skala Nilai: <strong>A = 4.0</strong> (Sangat Baik), <strong>B = 3.0</strong> (Baik), <strong>C = 2.0</strong> (Cukup), <strong>D = 1.0</strong> (Kurang)</span>
                    <span class="text-[11px] text-slate-400">4 Aspek Evaluasi Kesantrian</span>
                </div>
                <div class="h-64 sm:h-72 w-full">
                    <canvas id="studentAdabChart"></canvas>
                </div>
            </div>

            <!-- Tab 3: Physical Growth Chart -->
            <div x-show="activeTab === 'physical'" class="space-y-3" style="display: none;">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Sumbu Kiri: <strong>Tinggi Badan (cm)</strong> &bull; Sumbu Kanan: <strong>Berat Badan (kg)</strong></span>
                    <span class="text-[11px] text-slate-400">Pemantauan Kesehatan Fisik</span>
                </div>
                <div class="h-64 sm:h-72 w-full">
                    <canvas id="studentPhysicalChart"></canvas>
                </div>
            </div>
        @endif
    </div>

    <!-- SECTION: RIWAYAT LAPORAN BULANAN -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Arsip Riwayat Laporan Bulanan Santri</h3>
            <span class="text-xs text-slate-500">{{ $reports->count() }} Laporan Tercatat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Periode Laporan</th>
                        <th class="px-6 py-3.5">Capaian Tahfidz</th>
                        <th class="px-6 py-3.5">Ibadah & Akhlak</th>
                        <th class="px-6 py-3.5">Status Laporan</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $rep)
                        @php $r = $rep->record; @endphp
                        <tr class="hover:bg-slate-50/60 transition text-xs">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $rep->period_title }}</div>
                                <div class="text-[11px] text-slate-400">
                                    Terbit: {{ \Carbon\Carbon::parse($rep->report_date)->translatedFormat('d M Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800">{{ $r?->getFieldValue('tahfidz_akumulasi') ?: ($r?->getFieldValue('tahfidz_setoran') ?: '-') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-700">{{ $r?->getFieldValue('adab_ibadah') ?? '-' }} / {{ $r?->getFieldValue('adab_akhlak') ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $rep->status == 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ ucfirst($rep->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('reports.show', $rep->id) }}" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Lihat Rincian Laporan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <a href="{{ route('reports.preview', $rep->id) }}" target="_blank" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Download / Preview PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                Belum ada laporan bulanan untuk santri ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

