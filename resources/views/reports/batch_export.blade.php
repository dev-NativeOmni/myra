@extends('layouts.app', [
    'header' => 'Ekspor Massal PDF Rapor',
    'subheader' => 'Unduh laporan bulanan satu kelas sekaligus dalam 1 berkas PDF gabungan atau arsip ZIP'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('reports.batch-export.download') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Periode Laporan -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Periode Laporan <span class="text-rose-500">*</span></label>
            <select name="period_title" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white font-medium">
                @forelse($periods as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @empty
                    <option value="">-- Belum ada data periode --</option>
                @endforelse
            </select>
        </div>

        <!-- Kelas -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Kelas Target <span class="text-rose-500">*</span></label>
            <select name="classroom_id" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Kelas --</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}">Kelas {{ $room->name }} ({{ $room->students_count }} Santri)</option>
                @endforeach
            </select>
        </div>

        <!-- Format Pilihan -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-2">Format Berkas Ekspor <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30 transition">
                    <input type="radio" name="format" value="merged_pdf" checked class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <div class="text-xs font-bold text-slate-900">PDF Gabungan (Merged)</div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Satu berkas PDF berisi seluruh santri berurutan (1 santri = 1 lembar A4), paling cocok untuk dicetak langsung.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30 transition">
                    <input type="radio" name="format" value="zip" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <div class="text-xs font-bold text-slate-900">Arsip ZIP (.zip)</div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Berkas ZIP berisi file PDF terpisah untuk setiap santri dengan penamaan NIS dan Nama.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Tip Box -->
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3 text-xs text-emerald-800">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <span class="font-bold block mb-0.5">Presisi Tata Letak Terjamin:</span>
                Setiap lembar rapor dalam PDF gabungan diformat pas 1 halaman A4 persis, sehingga aman dikirim langsung ke mesin printer.
            </div>
        </div>

        <!-- Completeness Reminder -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3 text-xs text-amber-800">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <span class="font-bold block mb-0.5">Cek dulu sebelum ekspor:</span>
                Pastikan seluruh santri di kelas & periode terpilih sudah lengkap semua modulnya lewat
                <a href="{{ route('reports.completeness') }}" class="underline font-bold hover:text-amber-900">halaman Cek Kelengkapan</a>,
                supaya tidak ada laporan yang tercetak dengan data kosong.
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('reports.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
                Kembali
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Unduh Berkas Massal</span>
            </button>
        </div>
    </form>
</div>
@endsection

