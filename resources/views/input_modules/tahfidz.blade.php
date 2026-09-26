@extends('layouts.app', [
    'header' => 'Portal Input Guru Tahfidz',
    'subheader' => 'Input setoran hafalan, akumulasi tilawah, rincian juz, dan catatan evaluasi halaqah'
])

@section('content')
<div class="space-y-6">
    <!-- Top Action & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('modules.tahfidz') }}" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <select name="period_title" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period }}" {{ request('period_title') == $period ? 'selected' : '' }}>
                        {{ $period }}
                    </option>
                @endforeach
            </select>

            <select name="classroom_id" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white">
                <option value="">Semua Halaqah / Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ request('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('tahfidz-journals.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>Buka Jurnal Harian &rarr;</span>
            </a>
        </div>
    </div>

    <!-- Cards per Santri -->
    <div class="space-y-4">
        @forelse($reports as $rep)
            @php $rec = $rep->record; @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <!-- Sync from Journal Form (Separate Form) -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 mb-4 border-b border-slate-100 gap-3">
                    <div>
                        <span class="font-bold text-slate-900 text-base">{{ $rep->student->name }}</span>
                        <span class="text-xs text-slate-500 ml-2">NIS: {{ $rep->student->nis }} &bull; Kelas {{ $rep->student->classroom->name ?? '-' }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Auto-Sync Button -->
                        <form action="{{ route('reports.sync-tahfidz', $rep->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition" title="Tarik data otomatis dari catatan jurnal harian santri ini">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Tarik Otomatis dari Jurnal</span>
                            </button>
                        </form>

                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                            {{ $rep->period_title }}
                        </span>
                    </div>
                </div>

                <form action="{{ route('modules.tahfidz.update', $rep->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">1. Jumlah Setoran</label>
                            <input type="text" name="tahfidz_setoran" value="{{ old('tahfidz_setoran', $rec?->tahfidz_setoran) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="Tahsin / Ziyadah / 1 Juz">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">2. Akumulasi Tilawah</label>
                            <input type="text" name="tahfidz_akumulasi" value="{{ old('tahfidz_akumulasi', $rec?->tahfidz_akumulasi) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="33 Juz 1 Hal">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">3. Rincian Juz</label>
                            <input type="text" name="tahfidz_rincian_juz" value="{{ old('tahfidz_rincian_juz', $rec?->tahfidz_rincian_juz) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="1-30, 1-3">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">4. Catatan Evaluasi Tahfidz</label>
                        <textarea name="tahfidz_notes" rows="2"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                            placeholder="Tuliskan evaluasi perkembangan hafalan santri...">{{ old('tahfidz_notes', $rec?->tahfidz_notes) }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            Simpan Tahfidz
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="bg-white p-8 text-center text-slate-400 rounded-2xl border border-slate-200">
                Tidak ada data laporan untuk periode atau kelas yang dipilih.
            </div>
        @endforelse
    </div>

    @if($reports->hasPages())
        <div class="p-4 bg-white rounded-xl border border-slate-200">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection
