@extends('layouts.app', [
    'header' => 'Tambah Santri Baru',
    'subheader' => 'Daftarkan data santri baru ke dalam sistem'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('students.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Induk Santri (NIS) <span class="text-rose-500">*</span></label>
            <input type="text" name="nis" value="{{ old('nis') }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition font-mono"
                placeholder="Contoh: 0206">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Santri <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Contoh: Abdurrahman Faiz Al Hafidz">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kelas / Rombel <span class="text-rose-500">*</span></label>
            <select name="classroom_id" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Kelas --</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ old('classroom_id') == $room->id ? 'selected' : '' }}>
                        Kelas {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
            <div class="flex items-center gap-6 mt-1">
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="radio" name="gender" value="L" {{ old('gender', 'L') == 'L' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                    <span>Laki-laki (Ikhwan)</span>
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="radio" name="gender" value="P" {{ old('gender') == 'P' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                    <span>Perempuan (Akhwat)</span>
                </label>
            </div>
        </div>

        <div class="pt-2">
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded-sm text-emerald-600 focus:ring-emerald-500">
                <span>Santri Berstatus Aktif</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('students.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                Simpan Santri
            </button>
        </div>
    </form>
</div>
@endsection

