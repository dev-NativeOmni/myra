@extends('layouts.app', [
    'header' => 'Input Capaian Evaluasi Santri',
    'subheader' => $report->student->name . ' (NIS: ' . $report->student->nis . ') - Periode: ' . $report->period_title
])

@section('content')
<form action="{{ route('reports.update-record', $report->id) }}" method="POST" class="space-y-8 max-w-4xl">
    @csrf
    @method('PUT')

    <!-- Card 0: Header Info Laporan -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Status & Periode Laporan</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Periode</label>
                <input type="text" name="period_title" value="{{ old('period_title', $report->period_title) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition uppercase">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Terbit Dokumen</label>
                <input type="date" name="report_date" value="{{ old('report_date', \Carbon\Carbon::parse($report->report_date)->format('Y-m-d')) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Status Publikasi</label>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                    <option value="draft" {{ old('status', $report->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $report->status) == 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Modul A: Tahfidz -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-3 mb-5">
            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">A</span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Modul Tahfidz Al-Qur'an</h2>
                <p class="text-xs text-slate-500">Input setoran hafalan, akumulasi tilawah, dan catatan perkembangan halaqah.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Jumlah Setoran</label>
                <input type="text" name="tahfidz_setoran" value="{{ old('tahfidz_setoran', $record->tahfidz_setoran) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Tahsin / Ziyadah / 1 Juz">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Akumulasi Tilawah</label>
                <input type="text" name="tahfidz_akumulasi" value="{{ old('tahfidz_akumulasi', $record->tahfidz_akumulasi) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: 33 Juz 1 Hal">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">3. Rincian Juz</label>
                <input type="text" name="tahfidz_rincian_juz" value="{{ old('tahfidz_rincian_juz', $record->tahfidz_rincian_juz) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: 1-30, 1-3">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">4. Catatan Evaluasi Tahfidz</label>
                <textarea name="tahfidz_notes" rows="3"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Tuliskan evaluasi capaian halaqah santri...">{{ old('tahfidz_notes', $record->tahfidz_notes) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Modul B: Kesantrian -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-3 mb-5">
            <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">B</span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Modul Kesantrian</h2>
                <p class="text-xs text-slate-500">Penilaian adab karakter, data fisik pertumbuhan, dan catatan wali asrama.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Ibadah</label>
                <input type="text" name="adab_ibadah" value="{{ old('adab_ibadah', $record->adab_ibadah) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="A / B (Baik)">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Akhlak</label>
                <input type="text" name="adab_akhlak" value="{{ old('adab_akhlak', $record->adab_akhlak) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="A / B (Baik)">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">3. Kerapian</label>
                <input type="text" name="adab_kerapian" value="{{ old('adab_kerapian', $record->adab_kerapian) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="A / B (Baik)">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">4. Kedisiplinan</label>
                <input type="text" name="adab_kedisiplinan" value="{{ old('adab_kedisiplinan', $record->adab_kedisiplinan) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="A / B (Baik)">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5 p-4 bg-slate-50/75 rounded-xl border border-slate-100">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">5. Tinggi Badan (Cm)</label>
                <input type="number" name="body_height_cm" value="{{ old('body_height_cm', $record->body_height_cm) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white"
                    placeholder="Contoh: 133">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">6. Berat Badan (Kg)</label>
                <input type="number" name="body_weight_kg" value="{{ old('body_weight_kg', $record->body_weight_kg) }}"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white"
                    placeholder="Contoh: 28">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">7. Status Baligh</label>
                <select name="is_baligh"
                    class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                    <option value="Belum" {{ old('is_baligh', $record->is_baligh) == 'Belum' ? 'selected' : '' }}>Belum</option>
                    <option value="Sudah" {{ old('is_baligh', $record->is_baligh) == 'Sudah' ? 'selected' : '' }}>Sudah</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">8. Catatan Evaluasi Kesantrian</label>
            <textarea name="kesantrian_notes" rows="3"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Tuliskan evaluasi keaktifan dan perilaku ananda di asrama...">{{ old('kesantrian_notes', $record->kesantrian_notes) }}</textarea>
        </div>
    </div>

    <!-- Modul C: Akademik -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-3 mb-5">
            <span class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">C</span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Modul Akademik / Wali Kelas</h2>
                <p class="text-xs text-slate-500">Evaluasi keaktifan belajar dan capaian kegiatan belajar mengajar formal di kelas.</p>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Catatan Evaluasi Akademik</label>
            <textarea name="academic_notes" rows="3"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Tuliskan evaluasi KBM santri di kelas...">{{ old('academic_notes', $record->academic_notes) }}</textarea>
        </div>
    </div>

    <!-- Modul D: Administrasi -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <div class="flex items-center gap-3 mb-5">
            <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold text-xs">D</span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Modul Administrasi & Keuangan</h2>
                <p class="text-xs text-slate-500">Status pelunasan pembayaran SPP, laundry, dan daftar ulang.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. SPP Terakhir</label>
                <input type="text" name="last_spp" value="{{ old('last_spp', $record->last_spp) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Agustus 2026">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Laundry Terakhir</label>
                <input type="text" name="last_laundry" value="{{ old('last_laundry', $record->last_laundry) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Agustus 2026">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">3. Status Daftar Ulang</label>
                <input type="text" name="registration_status" value="{{ old('registration_status', $record->registration_status) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Lunas / Belum Lunas">
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <a href="{{ route('reports.show', $report->id) }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
            Batal
        </a>
        <button type="submit" class="px-7 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
            Simpan Seluruh Capaian
        </button>
    </div>
</form>
@endsection

