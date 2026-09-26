@extends('layouts.app', [
    'header' => 'Catat Setoran Jurnal Tahfidz',
    'subheader' => 'Input setoran hafalan harian santri di halaqah Al-Qur\'an'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('tahfidz-journals.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Santri <span class="text-rose-500">*</span></label>
            <select name="student_id" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Santri --</option>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ old('student_id') == $st->id ? 'selected' : '' }}>
                        {{ $st->name }} (NIS: {{ $st->nis }} - Kelas {{ $st->classroom->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Setoran <span class="text-rose-500">*</span></label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Setoran <span class="text-rose-500">*</span></label>
                <select name="type" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white font-medium">
                    <option value="ziyadah" {{ old('type') == 'ziyadah' ? 'selected' : '' }}>Ziyadah (Hafalan Baru)</option>
                    <option value="murajaah" {{ old('type') == 'murajaah' ? 'selected' : '' }}>Muraja'ah (Ulang Hafalan)</option>
                    <option value="tahsin" {{ old('type') == 'tahsin' ? 'selected' : '' }}>Tahsin (Perbaikan Bacaan)</option>
                    <option value="tilawah" {{ old('type') == 'tilawah' ? 'selected' : '' }}>Tilawah Mandiri</option>
                    <option value="tasmi" {{ old('type') == 'tasmi' ? 'selected' : '' }}>Tasmi' Ujian</option>
                </select>
            </div>
        </div>

        <!-- Rincian Capaian -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-4">
            <div class="text-xs font-bold text-slate-800">Rincian Surat & Ayat</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Juz</label>
                    <input type="number" name="juz" value="{{ old('juz') }}" min="1" max="30"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white"
                        placeholder="Contoh: 30">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Surat</label>
                    <input type="text" name="surah" value="{{ old('surah') }}"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white"
                        placeholder="Contoh: An-Naba'">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Dari Ayat s/d Ayat</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="ayah_start" value="{{ old('ayah_start') }}" min="1"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white"
                            placeholder="Ayat 1">
                        <span class="text-slate-400">-</span>
                        <input type="number" name="ayah_end" value="{{ old('ayah_end') }}" min="1"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white"
                            placeholder="Ayat 40">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Jumlah Halaman</label>
                    <input type="number" name="page_count" value="{{ old('page_count', 1) }}" min="1"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 outline-hidden bg-white"
                        placeholder="Contoh: 2">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Penilaian / Predikat Kelancaran</label>
            <select name="grade"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="Mumtaz (Sangat Baik)" {{ old('grade') == 'Mumtaz (Sangat Baik)' ? 'selected' : '' }}>Mumtaz (Sangat Baik - A)</option>
                <option value="Jayyid Jiddan (Baik Sekali)" {{ old('grade') == 'Jayyid Jiddan (Baik Sekali)' ? 'selected' : '' }}>Jayyid Jiddan (Baik Sekali - B+)</option>
                <option value="Jayyid (Baik)" {{ old('grade', 'Jayyid (Baik)') == 'Jayyid (Baik)' ? 'selected' : '' }}>Jayyid (Baik - B)</option>
                <option value="Maqbul (Cukup)" {{ old('grade') == 'Maqbul (Cukup)' ? 'selected' : '' }}>Maqbul (Cukup - C)</option>
                <option value="Rasib (Perlu Ulang)" {{ old('grade') == 'Rasib (Perlu Ulang)' ? 'selected' : '' }}>Rasib (Perlu Ulang / Belum Lancar - D)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Tajwid, Makhorijul Huruf & Kelancaran</label>
            <textarea name="notes" rows="3"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Catatan perkembangan halaqah santri...">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('tahfidz-journals.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                Simpan Setoran
            </button>
        </div>
    </form>
</div>
@endsection

