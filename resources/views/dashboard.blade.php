@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Hero / Welcome Banner -->
    <div class="p-6 rounded-2xl bg-linear-to-r from-emerald-800 to-teal-900 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-700/60 text-emerald-200 border border-emerald-500/30 mb-3">
                {{ $institution->name ?? 'Pondok Pesantren Contoh' }} &bull; {{ $institution->city ?? 'Kota Contoh' }}
            </span>
            <h2 class="text-2xl font-bold">Selamat Datang di Portal Myra</h2>
            <p class="text-emerald-100/80 text-sm mt-1 max-w-2xl">
                Sistem Laporan Bulanan Santri modular & white-label dengan output PDF A4 presisi satu halaman standar siap cetak.
            </p>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Total Santri Aktif</p>
                <p class="text-2xl font-bold text-slate-800">{{ $totalStudents }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Total Kelas / Rombel</p>
                <p class="text-2xl font-bold text-slate-800">{{ $totalClassrooms }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Laporan Bulanan</p>
                <p class="text-2xl font-bold text-slate-800">{{ $totalReports }}</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500">Pimpinan Lembaga</p>
                <p class="text-sm font-bold text-slate-800 truncate max-w-[150px]">{{ $institution->director_name ?? 'Ust. Fulan' }}</p>
            </div>
        </div>
    </div>

    <!-- Analytics & Monitoring Overview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 1. Progress Bar Kurikulum Per Kelas (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Capaian Target Kurikulum Kelas</h3>
                        <p class="text-[11px] text-slate-400">Rata-rata progres hafalan santri per jenjang SD/MI</p>
                    </div>
                </div>
                <a href="{{ route('analytics.index') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                    Analitik Lengkap &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                @foreach ($classAnalytics as $ca)
                    @php
                        $pct = $ca['average_percentage'];
                        $barColor = $pct >= 80 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-teal-500' : 'bg-amber-500');
                    @endphp
                    <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/70 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-slate-800">Kelas {{ $ca['classroom']->name }}</span>
                            <span class="text-emerald-700 font-black">{{ $pct }}%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full {{ $barColor }} rounded-full transition-all duration-500"
                                 style="width: {{ min(100, max(5, $pct)) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                            <span>Target: {{ $ca['target_label'] }}</span>
                            <span>{{ $ca['completed_students'] }}/{{ $ca['total_students'] }} Tuntas</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. Early Warning Alert Card (1 Col) -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Early Warning System</h3>
                            <p class="text-[11px] text-slate-400">Santri butuh pendampingan khusus</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                        {{ $summaryStats['total_warning_students'] }} Santri
                    </span>
                </div>

                <div class="space-y-2.5 mt-3">
                    @forelse ($earlyWarningStudents as $ews)
                        @php
                            $st = $ews['student'];
                            $badge = $ews['risk_level'] === 'critical' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800';
                        @endphp
                        <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/50 flex items-center justify-between gap-2 hover:bg-slate-100 transition">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ $st->name }}</p>
                                <p class="text-[10px] text-slate-500 truncate">
                                    Kelas {{ $st->classroom->name }} &bull; {{ $ews['primary_reason'] }}
                                </p>
                            </div>
                            @if ($ews['wa_link'])
                                <a href="{{ $ews['wa_link'] }}" target="_blank"
                                   class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold inline-flex items-center gap-1 shrink-0"
                                   title="Hubungi via WhatsApp">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <span>WA</span>
                                </a>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Semua santri dalam performa baik.</p>
                    @endforelse
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 text-center">
                <a href="{{ route('analytics.index') }}" class="text-xs font-bold text-slate-600 hover:text-emerald-600 transition">
                    Lihat Semua Santri Terdeteksi &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Action Hub -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <h3 class="text-base font-bold text-slate-900 mb-4">Aksi Cepat Manajemen</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('institution.edit') }}" class="p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition group">
                <div class="font-semibold text-slate-800 group-hover:text-emerald-700 text-sm">Pengaturan Lembaga &rarr;</div>
                <p class="text-xs text-slate-500 mt-1">Ubah nama, logo, stempel, dan tanda tangan resmi</p>
            </a>

            <a href="{{ route('classrooms.index') }}" class="p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition group">
                <div class="font-semibold text-slate-800 group-hover:text-emerald-700 text-sm">Kelola Kelas &rarr;</div>
                <p class="text-xs text-slate-500 mt-1">Tambah atau sesuaikan daftar kelas & rombel</p>
            </a>

            <a href="{{ route('students.index') }}" class="p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition group">
                <div class="font-semibold text-slate-800 group-hover:text-emerald-700 text-sm">Kelola Santri &rarr;</div>
                <p class="text-xs text-slate-500 mt-1">Kelola data santri, NIS, dan penempatan kelas</p>
            </a>

            @if($recentReports->isNotEmpty())
                <a href="{{ route('reports.preview', $recentReports->first()->id) }}" target="_blank" class="p-4 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 transition group">
                    <div class="font-semibold text-emerald-800 text-sm">Pratinjau PDF Sampel &rarr;</div>
                    <p class="text-xs text-emerald-700/80 mt-1">Buka hasil cetak PDF A4 presisi satu halaman</p>
                </a>
            @endif
        </div>
    </div>

    <!-- Recent Reports Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Laporan Bulanan Terakhir</h3>
            <span class="text-xs text-slate-500">Menampilkan {{ $recentReports->count() }} laporan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Santri</th>
                        <th class="px-6 py-3.5">NIS / Kelas</th>
                        <th class="px-6 py-3.5">Periode</th>
                        <th class="px-6 py-3.5">Tanggal Terbit</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentReports as $rep)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $rep->student->name }}</td>
                            <td class="px-6 py-4">{{ $rep->student->nis }} &bull; Kelas {{ $rep->student->classroom->name }}</td>
                            <td class="px-6 py-4 font-medium text-emerald-700">{{ $rep->period_title }}</td>
                            <td class="px-6 py-4 text-xs">{{ \Carbon\Carbon::parse($rep->report_date)->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium bg-emerald-100 text-emerald-800">
                                    {{ ucfirst($rep->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('reports.preview', $rep->id) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Cetak PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Belum ada laporan yang dibuat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

