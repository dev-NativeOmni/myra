@extends('layouts.app', [
    'header' => 'Edit Akun Pengguna',
    'subheader' => 'Perbarui data nama, email, peran, atau tautan santri pengguna'
])

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
    <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username (Untuk Login) <span class="text-rose-500">*</span></label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition font-mono">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email (Opsional)</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ganti Kata Sandi (Kosongkan jika tidak ingin mengubah)</label>
            <input type="password" name="password" minlength="6"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                placeholder="Minimal 6 karakter">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Peran / Hak Akses (Role) <span class="text-rose-500">*</span></label>
            <select name="role" id="role-select" onchange="toggleStudentPicker(); toggleClassroomPicker();" required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white font-medium">
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}" {{ old('role', $user->role) == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Student Selector (only visible if role == wali_murid) -->
        <div id="student-picker" class="{{ old('role', $user->role) == 'wali_murid' ? '' : 'hidden' }}">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tautkan ke Data Santri (Wajib untuk Wali Murid) <span class="text-rose-500">*</span></label>
            <select name="student_id"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">-- Pilih Santri --</option>
                @foreach($students as $st)
                    <option value="{{ $st->id }}" {{ old('student_id', $user->student_id) == $st->id ? 'selected' : '' }}>
                        {{ $st->name }} (NIS: {{ $st->nis }} - Kelas {{ $st->classroom->name ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Classroom Assignment (only visible if role is guru, wali_kelas, or kesantrian) -->
        @php $assignedClassroomIds = old('classroom_ids', $user->classrooms->pluck('id')->toArray()); @endphp
        <div id="classroom-picker" class="{{ in_array(old('role', $user->role), ['guru', 'wali_kelas', 'kesantrian']) ? '' : 'hidden' }}">
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kelas / Halaqah Tanggung Jawab</label>
            <p class="text-[11px] text-slate-400 mb-2">Pengguna ini hanya dapat mengedit data santri pada kelas yang dipilih. Kelas lain tetap bisa dilihat tapi tidak bisa diubah.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-3">
                @forelse($classrooms as $cls)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="classroom_ids[]" value="{{ $cls->id }}"
                            {{ in_array($cls->id, $assignedClassroomIds) ? 'checked' : '' }}
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
                Simpan Perubahan
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

