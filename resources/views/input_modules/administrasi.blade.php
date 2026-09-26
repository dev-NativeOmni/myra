@extends('layouts.app', [
    'header' => 'Portal Input Tata Usaha / Administrasi',
    'subheader' => 'Input status pembayaran SPP, laundry, dan daftar ulang santri'
])

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('modules.administrasi') }}" class="flex flex-wrap items-center gap-3">
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
                <option value="">Semua Kelas</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ request('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <span class="text-xs text-slate-500">Total {{ $reports->total() }} Santri</span>
    </div>

    <!-- Cards per Santri -->
    <div class="space-y-4">
        @forelse($reports as $rep)
            @php $rec = $rep->record; @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
                <form action="{{ route('modules.administrasi.update', $rep->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 mb-4 border-b border-slate-100 gap-2">
                        <div>
                            <span class="font-bold text-slate-900 text-base">{{ $rep->student->name }}</span>
                            <span class="text-xs text-slate-500 ml-2">NIS: {{ $rep->student->nis }} &bull; Kelas {{ $rep->student->classroom->name ?? '-' }}</span>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-purple-50 text-purple-800 border border-purple-200">
                            {{ $rep->period_title }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">1. SPP Terakhir</label>
                            <input type="text" name="last_spp" value="{{ old('last_spp', $rec?->last_spp) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="Contoh: Agustus 2026">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">2. Laundry Terakhir</label>
                            <input type="text" name="last_laundry" value="{{ old('last_laundry', $rec?->last_laundry) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="Contoh: Agustus 2026">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">3. Status Daftar Ulang</label>
                            <input type="text" name="registration_status" value="{{ old('registration_status', $rec?->registration_status) }}"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="Lunas / Belum Lunas">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            Simpan Administrasi
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

