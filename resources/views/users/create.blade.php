@extends('layouts.app', [
    'header' => 'Tambah Pengguna Baru',
    'subheader' => 'Daftarkan akun staf pengajar, administrator, atau wali murid baru'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('users.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Contoh: Ustadz Ahmad Fauzi / Ibu Siti">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username (Untuk Login) <span class="text-rose-500">*</span></label>
            <input type="text" name="username" value="{{ old('username') }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition font-mono"
                placeholder="contoh: ust_ahmad / ahmad123">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email (Opsional)</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="nama@email.com (bisa dikosongkan)">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Awal <span class="text-rose-500">*</span></label>
            <input type="password" name="password" required minlength="8"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Minimal 8 karakter">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Peran / Hak Akses (Role) <span class="text-rose-500">*</span></label>
            <select name="role" id="role-select" onchange="toggleStudentPicker(); toggleClassroomPicker();" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white font-medium">
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Student Selector (only visible if role == wali_murid) -->
        @include('users.partials.student-picker', ['visible' => old('role') == 'wali_murid', 'selectedStudentIds' => []])

        <!-- Classroom Assignment (only visible if role is guru, wali_kelas, or kesantrian) -->
        <div id="classroom-picker" class="{{ in_array(old('role'), ['guru', 'wali_kelas', 'kesantrian']) ? '' : 'hidden' }}">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kelas / Halaqah Tanggung Jawab</label>
            <p class="text-[11px] text-slate-400 mb-2">Pengguna ini hanya dapat melihat dan mengubah data santri pada kelas yang dipilih.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-3">
                @forelse($classrooms as $cls)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="classroom_ids[]" value="{{ $cls->id }}"
                            {{ in_array($cls->id, old('classroom_ids', [])) ? 'checked' : '' }}
                            class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Kelas {{ $cls->name }}
                    </label>
                @empty
                    <span class="text-xs text-slate-400 col-span-full">Belum ada data kelas.</span>
                @endforelse
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
            <a href="{{ route('users.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-900 text-sm font-medium transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
                Simpan Pengguna
            </button>
        </div>
    </form>
</div>

<script>
    function toggleStudentPicker() {
        const role = document.getElementById('role-select').value;
        const picker = document.getElementById('student-picker');
        if (role === 'wali_murid') {
            picker.classList.remove('hidden');
        } else {
            picker.classList.add('hidden');
        }
    }

    function toggleClassroomPicker() {
        const role = document.getElementById('role-select').value;
        const picker = document.getElementById('classroom-picker');
        if (['guru', 'wali_kelas', 'kesantrian'].includes(role)) {
            picker.classList.remove('hidden');
        } else {
            picker.classList.add('hidden');
        }
    }
</script>
@endsection

