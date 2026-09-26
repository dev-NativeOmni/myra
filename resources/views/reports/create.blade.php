@extends('layouts.app', [
    'header' => 'Buat / Generate Siklus Laporan Bulanan',
    'subheader' => 'Inisialisasi lembar laporan evaluasi bulanan untuk satu santri atau satu kelas sekaligus'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('reports.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Mode Pembuatan -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">Target Pembuatan Laporan <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30 transition">
                    <input type="radio" name="mode" value="single" checked onchange="toggleMode('single')" class="text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Satu Santri</div>
                        <div class="text-[11px] text-slate-500">Buat untuk 1 santri spesifik</div>
                    </div>
                </label>
                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30 transition">
                    <input type="radio" name="mode" value="classroom" onchange="toggleMode('classroom')" class="text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Satu Kelas Sekaligus</div>
                        <div class="text-[11px] text-slate-500">Generate seluruh santri di kelas</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Single Student Dropdown -->
        <div id="single-student-box">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Santri <span class="text-rose-500">*</span></label>
            <select name="student_id" id="student_id"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Santri Aktif --</option>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ old('student_id') == $st->id ? 'selected' : '' }}>
                        {{ $st->name }} (NIS: {{ $st->nis }} - Kelas {{ $st->classroom->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Classroom Dropdown -->
        <div id="classroom-box" class="hidden">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Kelas Target <span class="text-rose-500">*</span></label>
            <select name="classroom_id" id="classroom_id"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Kelas --</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ old('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }} ({{ $room->students->count() }} Santri)
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Periode & Tanggal -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Periode <span class="text-rose-500">*</span></label>
                <input type="text" name="period_title" value="{{ old('period_title', 'JULI-AGUSTUS 2026') }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition font-medium uppercase"
                    placeholder="Contoh: JULI-AGUSTUS 2026 atau SEMESTER 1">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Terbit Laporan <span class="text-rose-500">*</span></label>
                <input type="date" name="report_date" value="{{ old('report_date', date('Y-m-d')) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Cut-off Administrasi <span class="text-rose-500">*</span></label>
                <input type="date" name="cutoff_date" value="{{ old('cutoff_date', date('Y-m-d')) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Publikasi <span class="text-rose-500">*</span></label>
            <select name="status" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft (Masih dalam tahap pengisian nilai)</option>
                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published (Siap diunduh / cetak)</option>
            </select>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('reports.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                Buat Laporan &rarr;
            </button>
        </div>
    </form>
</div>

<script>
    function toggleMode(mode) {
        if (mode === 'classroom') {
            document.getElementById('single-student-box').classList.add('hidden');
            document.getElementById('classroom-box').classList.remove('hidden');
        } else {
            document.getElementById('single-student-box').classList.remove('hidden');
            document.getElementById('classroom-box').classList.add('hidden');
        }
    }
</script>
@endsection

