@extends('layouts.app', [
    'header' => 'Pengaturan Modul Penilaian',
    'subheader' => 'Kelola poin-poin penilaian, tambah indikator baru, dan sesuaikan kriteria pada 4 modul aktif'
])

@section('content')
<div class="space-y-6" x-data="{
    showAddModal: false,
    showEditModal: false,
    editData: {
        id: null,
        label: '',
        type: 'text',
        options_raw: '',
        placeholder: '',
        suffix: '',
        order_index: 1,
        is_active: true,
        is_system: false
    },
    openEdit(field) {
        this.editData = {
            id: field.id,
            label: field.label,
            type: field.type,
            options_raw: Array.isArray(field.options) ? field.options.join(', ') : '',
            placeholder: field.placeholder || '',
            suffix: field.suffix || '',
            order_index: field.order_index,
            is_active: field.is_active,
            is_system: field.is_system
        };
        this.showEditModal = true;
    }
}">

    <!-- Module Tabs Navigation -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($modules as $mKey => $mLabel)
                    @php
                        $isActive = ($selectedModule === $mKey);
                        $badgeColor = match($mKey) {
                            'tahfidz' => 'bg-emerald-500',
                            'kesantrian' => 'bg-blue-500',
                            'akademik' => 'bg-amber-500',
                            'administrasi' => 'bg-purple-500',
                            default => 'bg-slate-500'
                        };
                    @endphp
                    <a href="{{ route('module-settings.index', ['module' => $mKey]) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $isActive ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $badgeColor }}"></span>
                        <span>{{ $mLabel }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $isActive ? 'bg-slate-800 text-slate-300' : 'bg-slate-200 text-slate-700' }}">
                            {{ \App\Models\ModuleField::where('module', $mKey)->count() }}
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- Add Field Button -->
            <div class="flex items-center gap-2">
                <button type="button" @click="showAddModal = true"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-xs shadow-emerald-500/20 transition cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Poin Penilaian</span>
                </button>

                <form method="POST" action="{{ route('module-settings.reset') }}" onsubmit="return confirm('Kembalikan seluruh poin pada modul ini ke susunan standar awal?')">
                    @csrf
                    <input type="hidden" name="module" value="{{ $selectedModule }}">
                    <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition" title="Reset ke Default">
                        Reset Default
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Module Card Header -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    @php
                        $activeDotColor = match($selectedModule) {
                            'tahfidz' => 'bg-emerald-500',
                            'kesantrian' => 'bg-blue-500',
                            'akademik' => 'bg-amber-500',
                            'administrasi' => 'bg-purple-500',
                            default => 'bg-slate-500'
                        };
                    @endphp
                    <span class="w-3 h-3 rounded-full {{ $activeDotColor }}"></span>
                    <span>Modul {{ $modules[$selectedModule] }}</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar poin/indikator penilaian yang tampil pada form input guru dan cetak rapor bulanan santri.
                </p>
            </div>
            <div class="text-xs text-slate-500">
                Total: <span class="font-bold text-slate-800">{{ $fields->count() }} Poin</span>
                (<span class="text-emerald-700 font-semibold">{{ $fields->where('is_active', true)->count() }} Aktif</span>,
                <span class="text-slate-400">{{ $fields->where('is_active', false)->count() }} Nonaktif</span>)
            </div>
        </div>

        <!-- Table of Assessment Items -->
        <div class="overflow-x-auto mt-4">
            <table class="min-w-full divide-y divide-slate-200">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-500 bg-slate-50/80">
                        <th class="px-4 py-3 text-center w-16">Urutan</th>
                        <th class="px-4 py-3 text-left">Nama Poin (Label)</th>
                        <th class="px-4 py-3 text-left w-36">Tipe Input</th>
                        <th class="px-4 py-3 text-left">Pilihan Opsi / Keterangan</th>
                        <th class="px-4 py-3 text-center w-28">Status</th>
                        <th class="px-4 py-3 text-right w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($fields as $field)
                        <tr class="hover:bg-slate-50/60 transition {{ !$field->is_active ? 'opacity-60 bg-slate-50/40' : '' }}">
                            <!-- Order -->
                            <td class="px-4 py-3 text-center font-bold text-slate-500 font-mono">
                                #{{ $field->order_index }}
                            </td>

                            <!-- Label & Key -->
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $field->label }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5 flex items-center gap-1.5">
                                    <span>Key: {{ $field->key }}</span>
                                    @if ($field->is_system)
                                        <span class="px-1.5 py-0.2 rounded-sm bg-slate-100 text-slate-500 text-[9px]">Sistem</span>
                                    @else
                                        <span class="px-1.5 py-0.2 rounded-sm bg-emerald-50 text-emerald-700 text-[9px] font-semibold">Kustom</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="px-4 py-3">
                                @php
                                    $typeBadge = match($field->type) {
                                        'select' => ['bg-blue-50 text-blue-700 border-blue-200', 'Pilihan / Dropdown'],
                                        'number' => ['bg-amber-50 text-amber-700 border-amber-200', 'Angka / Nilai'],
                                        'textarea' => ['bg-purple-50 text-purple-700 border-purple-200', 'Catatan / Textarea'],
                                        default => ['bg-slate-100 text-slate-700 border-slate-200', 'Teks Singkat']
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $typeBadge[0] }}">
                                    {{ $typeBadge[1] }}
                                </span>
                                @if ($field->suffix)
                                    <span class="text-[10px] text-slate-400 ml-1">({{ $field->suffix }})</span>
                                @endif
                            </td>

                            <!-- Options / Notes -->
                            <td class="px-4 py-3">
                                @if ($field->type === 'select' && !empty($field->options))
                                    <div class="flex flex-wrap gap-1 max-w-md">
                                        @foreach ($field->options as $opt)
                                            <span class="px-2 py-0.5 rounded-sm bg-slate-100 text-slate-700 text-[10px] font-medium border border-slate-200">
                                                {{ $opt }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif ($field->placeholder)
                                    <span class="text-slate-400 italic text-[11px]">Placeholder: "{{ $field->placeholder }}"</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <!-- Active Status -->
                            <td class="px-4 py-3 text-center">
                                <form method="POST" action="{{ route('module-settings.toggle', $field->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition cursor-pointer {{ $field->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}"
                                            title="Klik untuk {{ $field->is_active ? 'menonaktifkan' : 'mengaktifkan' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $field->is_active ? 'bg-emerald-600' : 'bg-slate-400' }}"></span>
                                        <span>{{ $field->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit Button -->
                                    <button type="button" @click="openEdit({{ json_encode($field) }})"
                                            class="p-1.5 rounded-lg text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition" title="Edit Poin">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>

                                    <!-- Delete Button (Custom fields only) -->
                                    @if (!$field->is_system)
                                        <form method="POST" action="{{ route('module-settings.destroy', $field->id) }}" onsubmit="return confirm('Hapus poin penilaian ini? Nilai yang sudah tersimpan mungkin tidak lagi tampil.')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Poin">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                Belum ada poin penilaian pada modul ini. Klik tombol "Tambah Poin Penilaian" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: TAMBAH POIN PENILAIAN               -->
    <!-- ========================================== -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200/90 space-y-4" @click.outside="showAddModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Tambah Poin Penilaian Baru (Modul {{ $modules[$selectedModule] }})</span>
                </h4>
                <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('module-settings.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="module" value="{{ $selectedModule }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Poin / Indikator Penilaian <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" required placeholder="Contoh: Sholat Sunnah / Dhuha"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ selectedType: 'select' }">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Input <span class="text-rose-500">*</span></label>
                        <select name="type" x-model="selectedType" required
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden">
                            <option value="select">Pilihan / Predikat Dropdown</option>
                            <option value="text">Teks Singkat</option>
                            <option value="number">Angka / Nilai</option>
                            <option value="textarea">Catatan Paragraf / Textarea</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampilan</label>
                        <input type="number" name="order_index" value="{{ ($fields->max('order_index') ?? 0) + 1 }}" min="1"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>

                    <!-- Options for Select Type -->
                    <div class="col-span-2" x-show="selectedType === 'select'">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Daftar Pilihan Opsi (Pisahkan dengan koma)</label>
                        <input type="text" name="options_raw" value="A (Sangat Baik), B (Baik), C (Cukup), D (Kurang)" placeholder="Misal: A (Sangat Baik), B (Baik), C (Cukup), D (Kurang)"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                        <p class="text-[10px] text-slate-400 mt-0.5">Contoh untuk predikat: A (Sangat Baik), B (Baik), C (Cukup), D (Kurang)</p>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Satuan / Suffix (Opsional)</label>
                        <input type="text" name="suffix" placeholder="Contoh: cm, kg, Juz"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Placeholder (Opsional)</label>
                        <input type="text" name="placeholder" placeholder="Teks bantuan input..."
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Simpan Poin Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: EDIT POIN PENILAIAN                 -->
    <!-- ========================================== -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 border border-slate-200/90 space-y-4" @click.outside="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span>Ubah Poin Penilaian</span>
                </h4>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" :action="`{{ url('/module-settings') }}/${editData.id}`" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Poin / Indikator Penilaian <span class="text-rose-500">*</span></label>
                    <input type="text" name="label" x-model="editData.label" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Input</label>
                        <select name="type" x-model="editData.type" :disabled="editData.is_system"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium bg-white focus:ring-2 focus:ring-emerald-500 outline-hidden disabled:bg-slate-100 disabled:text-slate-500">
                            <option value="select">Pilihan / Predikat Dropdown</option>
                            <option value="text">Teks Singkat</option>
                            <option value="number">Angka / Nilai</option>
                            <option value="textarea">Catatan Paragraf / Textarea</option>
                        </select>
                        <template x-if="editData.is_system">
                            <span class="text-[9px] text-slate-400">Tipe field sistem dikunci.</span>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Urutan Tampilan</label>
                        <input type="number" name="order_index" x-model="editData.order_index" min="1" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>

                    <!-- Options for Select Type -->
                    <div class="col-span-2" x-show="editData.type === 'select'">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Daftar Pilihan Opsi (Pisahkan dengan koma)</label>
                        <input type="text" name="options_raw" x-model="editData.options_raw" placeholder="Misal: A (Sangat Baik), B (Baik), C (Cukup), D (Kurang)"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Satuan / Suffix</label>
                        <input type="text" name="suffix" x-model="editData.suffix" placeholder="Contoh: cm, kg, Juz"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Placeholder</label>
                        <input type="text" name="placeholder" x-model="editData.placeholder"
                               class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-emerald-500 outline-hidden">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" x-model="editData.is_active" class="rounded-sm border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>Poin Penilaian Aktif</span>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

