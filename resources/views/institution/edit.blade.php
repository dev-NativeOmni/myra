@extends('layouts.app', [
    'header' => 'Pengaturan Profil Lembaga',
    'subheader' => 'Sesuaikan identitas, aset visual stempel & tanda tangan, serta warna aksen rapor'
])

@section('content')
<form action="{{ route('institution.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
    @csrf
    @method('PUT')

    <!-- Card 1: Identitas Lembaga -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Identitas Lembaga</h2>
        <p class="text-xs text-slate-500 mb-6">Informasi ini akan muncul pada baris kop surat bagian atas rapor PDF.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lembaga / Pesantren <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $institution->name) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: PONDOK PESANTREN CONTOH">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sub-Judul / Jenjang Pendidikan</label>
                <input type="text" name="sub_title" value="{{ old('sub_title', $institution->sub_title) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: SMP / Madrasah Tsanawiyah">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kota / Kabupaten <span class="text-rose-500">*</span></label>
                <input type="text" name="city" value="{{ old('city', $institution->city) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: KOTA CONTOH">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Lengkap</label>
                <textarea name="address" rows="2"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Alamat jalan, kelurahan, kecamatan...">{{ old('address', $institution->address) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Kontak / Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $institution->phone) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="0812-xxxx-xxxx">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Aksen Tabel Rapor (Hex Code)</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="accent_color_picker" id="accent_color_picker" value="{{ old('accent_color', $institution->accent_color ?? '#059669') }}"
                        oninput="document.getElementById('accent_color').value = this.value"
                        class="w-10 h-10 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                    <input type="text" name="accent_color" id="accent_color" value="{{ old('accent_color', $institution->accent_color ?? '#059669') }}" required
                        oninput="document.getElementById('accent_color_picker').value = this.value"
                        class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition uppercase font-mono">
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Pengesahan & Pimpinan -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Pejabat Pengesahan (Pimpinan)</h2>
        <p class="text-xs text-slate-500 mb-6">Data penandatangan rapor pada blok tanda tangan di kanan bawah dokumen PDF.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap & Gelar Pimpinan <span class="text-rose-500">*</span></label>
                <input type="text" name="director_name" value="{{ old('director_name', $institution->director_name) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Ust. Fulan, S.Pd.">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jabatan Resmi <span class="text-rose-500">*</span></label>
                <input type="text" name="director_title" value="{{ old('director_title', $institution->director_title) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition"
                    placeholder="Contoh: Direktur Pesantren / Mudir / Kepala Sekolah">
            </div>
        </div>
    </div>

    <!-- Card 3: Terminologi Antarmuka (White-Label) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Terminologi Antarmuka</h2>
        <p class="text-xs text-slate-500 mb-6">Sesuaikan istilah yang tampil di seluruh sistem dan rapor cetak dengan kebiasaan lembaga Anda.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sebutan Peserta Didik</label>
                <select name="term_student" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                    <option value="Santri" {{ old('term_student', $institution->term_student) === 'Santri' ? 'selected' : '' }}>Santri</option>
                    <option value="Siswa" {{ old('term_student', $institution->term_student) === 'Siswa' ? 'selected' : '' }}>Siswa</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sebutan Pengajar</label>
                <select name="term_teacher" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                    <option value="Guru" {{ old('term_teacher', $institution->term_teacher) === 'Guru' ? 'selected' : '' }}>Guru</option>
                    <option value="Musyrif" {{ old('term_teacher', $institution->term_teacher) === 'Musyrif' ? 'selected' : '' }}>Musyrif</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sebutan Rombongan Belajar</label>
                <select name="term_class" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">
                    <option value="Kelas" {{ old('term_class', $institution->term_class) === 'Kelas' ? 'selected' : '' }}>Kelas</option>
                    <option value="Halaqah" {{ old('term_class', $institution->term_class) === 'Halaqah' ? 'selected' : '' }}>Halaqah</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card 4: Aset Visual White-Label (Upload) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Aset Visual Dokumen</h2>
        <p class="text-xs text-slate-500 mb-6">Boleh pilih PNG, JPG, atau WebP (maks. 2 MB per file). File otomatis diubah ke JPG berlatar putih agar bisa dicetak di rapor PDF.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <!-- Logo -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1">1. Logo Lembaga</label>
                    <p class="text-[11px] text-slate-500 mb-3">Tampil di tengah atas kop surat rapor.</p>
                    
                    @if($institution->logo_path)
                        <div class="mb-3 p-2 bg-white rounded-lg border border-slate-200 text-center">
                            <img src="{{ $institution->imageUrl('logo_path') }}" alt="Logo" class="max-h-16 mx-auto object-contain">
                        </div>
                    @endif
                </div>

                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" data-convert-to-jpeg
                    class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
            </div>

            <!-- Stempel -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1">2. Stempel / Cap Resmi</label>
                    <p class="text-[11px] text-slate-500 mb-3">Tampil di sisi kiri area tanda tangan.</p>
                    
                    @if($institution->stamp_path)
                        <div class="mb-3 p-2 bg-white rounded-lg border border-slate-200 text-center">
                            <img src="{{ $institution->imageUrl('stamp_path') }}" alt="Stempel" class="max-h-16 mx-auto object-contain">
                        </div>
                    @endif
                </div>

                <input type="file" name="stamp" accept="image/png,image/jpeg,image/webp" data-convert-to-jpeg
                    class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
            </div>

            <!-- Tanda Tangan -->
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1">3. Tanda Tangan Pimpinan</label>
                    <p class="text-[11px] text-slate-500 mb-3">Tanda tangan digital pimpinan.</p>
                    
                    @if($institution->signature_path)
                        <div class="mb-3 p-2 bg-white rounded-lg border border-slate-200 text-center">
                            <img src="{{ $institution->imageUrl('signature_path') }}" alt="Tanda Tangan" class="max-h-16 mx-auto object-contain">
                        </div>
                    @endif
                </div>

                <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" data-convert-to-jpeg
                    class="text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
            </div>
        </div>
    </div>

    <!-- Submit Action Bar -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-xs transition">
            Simpan Perubahan Profil
        </button>
    </div>
</form>

<script>
    /**
     * The serverless PDF renderer has no GD extension, so it can only embed JPEG images.
     * Convert PNG/WebP uploads to JPEG on a white background before the form is sent.
     */
    document.querySelectorAll('input[data-convert-to-jpeg]').forEach((input) => {
        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file || file.type === 'image/jpeg') {
                return;
            }

            const bitmap = await createImageBitmap(file);
            const canvas = document.createElement('canvas');
            canvas.width = bitmap.width;
            canvas.height = bitmap.height;
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(bitmap, 0, 0);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
            const jpegName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], jpegName, { type: 'image/jpeg' }));
            input.files = transfer.files;
        });
    });
</script>
@endsection

