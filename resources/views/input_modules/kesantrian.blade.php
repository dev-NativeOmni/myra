@extends('layouts.app', [
    'header' => 'Portal Input Wali Asrama / Kesantrian',
    'subheader' => 'Input penilaian adab ibadah, akhlak, kedisiplinan, data fisik, dan catatan asrama'
])

@section('content')
<div class="space-y-6">
    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('modules.kesantrian') }}" class="flex flex-wrap items-center gap-3">
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
                <form action="{{ route('modules.kesantrian.update', $rep->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 mb-4 border-b border-slate-100 gap-2">
                        <div>
                            <span class="font-bold text-slate-900 text-base">{{ $rep->student->name }}</span>
                            <span class="text-xs text-slate-500 ml-2">NIS: {{ $rep->student->nis }} &bull; Kelas {{ $rep->student->classroom->name ?? '-' }}</span>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200">
                            {{ $rep->period_title }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">1. Ibadah</label>
                            <input type="text" name="adab_ibadah" value="{{ old('adab_ibadah', $rec?->adab_ibadah) }}"
                                class="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="B (Baik)">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">2. Akhlak</label>
                            <input type="text" name="adab_akhlak" value="{{ old('adab_akhlak', $rec?->adab_akhlak) }}"
                                class="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="B (Baik)">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">3. Kerapian</label>
                            <input type="text" name="adab_kerapian" value="{{ old('adab_kerapian', $rec?->adab_kerapian) }}"
                                class="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="B (Baik)">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">4. Kedisiplinan</label>
                            <input type="text" name="adab_kedisiplinan" value="{{ old('adab_kedisiplinan', $rec?->adab_kedisiplinan) }}"
                                class="w-full px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="B (Baik)">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-4 p-3 bg-slate-50 rounded-xl">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">5. Tinggi Badan (Cm)</label>
                            <input type="number" name="body_height_cm" value="{{ old('body_height_cm', $rec?->body_height_cm) }}"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="133">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">6. Berat Badan (Kg)</label>
                            <input type="number" name="body_weight_kg" value="{{ old('body_weight_kg', $rec?->body_weight_kg) }}"
                                class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden"
                                placeholder="28">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">7. Status Baligh</label>
                            <select name="is_baligh" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden">
                                <option value="Belum" {{ old('is_baligh', $rec?->is_baligh) == 'Belum' ? 'selected' : '' }}>Belum</option>
                                <option value="Sudah" {{ old('is_baligh', $rec?->is_baligh) == 'Sudah' ? 'selected' : '' }}>Sudah</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">8. Catatan Evaluasi Kesantrian</label>
                        <textarea name="kesantrian_notes" rows="2"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden"
                            placeholder="Tuliskan evaluasi sikap dan kedisiplinan santri di asrama...">{{ old('kesantrian_notes', $rec?->kesantrian_notes) }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            Simpan Kesantrian
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

