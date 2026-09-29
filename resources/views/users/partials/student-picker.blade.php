{{-- Linked children for a wali murid account; a parent may be linked to several students (siblings). --}}
@php
    $selectedStudentIds = collect(old('student_ids', $selectedStudentIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp
<div id="student-picker" class="{{ $visible ? '' : 'hidden' }}" x-data="{ search: '' }">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tautkan ke Data Santri (Wajib untuk Wali Murid) <span class="text-rose-500">*</span></label>
    <p class="text-[11px] text-slate-400 mb-2">Centang semua anak dari wali ini. Wali dapat berpindah antar-anak di portalnya.</p>
    <input type="search" x-model="search" placeholder="Cari nama atau NIS santri..." aria-label="Cari santri"
        class="w-full mb-2 px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
    <div class="max-h-56 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-0.5">
        @forelse($students as $st)
            <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
                   data-search="{{ Str::lower($st->name.' '.$st->nis) }}"
                   x-show="$el.dataset.search.includes(search.trim().toLowerCase())">
                <input type="checkbox" name="student_ids[]" value="{{ $st->id }}"
                    {{ in_array($st->id, $selectedStudentIds, true) ? 'checked' : '' }}
                    class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>{{ $st->name }} <span class="text-xs text-slate-400">(NIS: {{ $st->nis }} &bull; Kelas {{ $st->classroom->name ?? '-' }})</span></span>
            </label>
        @empty
            <span class="text-xs text-slate-400 block p-2">Belum ada data santri aktif.</span>
        @endforelse
    </div>
</div>
