<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ModuleField extends Model
{
    use HasFactory;

    public const MODULE_TAHFIDZ = 'tahfidz';

    public const MODULE_KESANTRIAN = 'kesantrian';

    public const MODULE_AKADEMIK = 'akademik';

    public const MODULE_ADMINISTRASI = 'administrasi';

    public const MODULES = [
        self::MODULE_TAHFIDZ => 'Tahfidz Al-Qur\'an',
        self::MODULE_KESANTRIAN => 'Kesantrian',
        self::MODULE_AKADEMIK => 'Wali Kelas (Akademik)',
        self::MODULE_ADMINISTRASI => 'Tata Usaha (Administrasi)',
    ];

    protected $fillable = [
        'module',
        'key',
        'label',
        'type',
        'options',
        'placeholder',
        'suffix',
        'order_index',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'order_index' => 'integer',
    ];

    /**
     * Get active fields for a module, initializing defaults if table is empty.
     */
    public static function getActiveFields(string $module)
    {
        if (static::count() === 0) {
            static::seedDefaultFields();
        }

        return static::where('module', $module)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get all fields for a module.
     */
    public static function getAllFields(string $module)
    {
        if (static::count() === 0) {
            static::seedDefaultFields();
        }

        return static::where('module', $module)
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();
    }

    /**
     * Map every field key to its owning module, e.g. ['tahfidz_notes' => 'tahfidz'].
     * Used to authorize which fields a role may write.
     *
     * @return Collection<string, string>
     */
    public static function fieldModuleMap()
    {
        if (static::count() === 0) {
            static::seedDefaultFields();
        }

        return static::pluck('module', 'key');
    }

    /**
     * Seed default fields if they do not exist.
     */
    public static function seedDefaultFields(): void
    {
        $defaultFields = [
            // Tahfidz
            [
                'module' => self::MODULE_TAHFIDZ,
                'key' => 'tahfidz_setoran',
                'label' => '1. Jumlah Setoran',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Tahsin / Ziyadah',
                'suffix' => null,
                'order_index' => 1,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_TAHFIDZ,
                'key' => 'tahfidz_akumulasi',
                'label' => '2. Akumulasi Tilawah',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Misal: 33 Juz 1 Hal',
                'suffix' => null,
                'order_index' => 2,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_TAHFIDZ,
                'key' => 'tahfidz_rincian_juz',
                'label' => '3. Rincian Juz',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Misal: 1-30, 1-3',
                'suffix' => null,
                'order_index' => 3,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_TAHFIDZ,
                'key' => 'tahfidz_notes',
                'label' => '4. Catatan Tahfidz',
                'type' => 'textarea',
                'options' => null,
                'placeholder' => 'Catatan evaluasi tahfidz...',
                'suffix' => null,
                'order_index' => 4,
                'is_active' => true,
                'is_system' => true,
            ],

            // Kesantrian
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'adab_ibadah',
                'label' => '1. Ibadah',
                'type' => 'select',
                'options' => ['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)'],
                'placeholder' => '- Pilih -',
                'suffix' => null,
                'order_index' => 1,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'adab_akhlak',
                'label' => '2. Akhlak',
                'type' => 'select',
                'options' => ['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)'],
                'placeholder' => '- Pilih -',
                'suffix' => null,
                'order_index' => 2,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'adab_kerapian',
                'label' => '3. Kerapian',
                'type' => 'select',
                'options' => ['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)'],
                'placeholder' => '- Pilih -',
                'suffix' => null,
                'order_index' => 3,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'adab_kedisiplinan',
                'label' => '4. Disiplin',
                'type' => 'select',
                'options' => ['A (Sangat Baik)', 'B (Baik)', 'C (Cukup)', 'D (Kurang)'],
                'placeholder' => '- Pilih -',
                'suffix' => null,
                'order_index' => 4,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'body_height_cm',
                'label' => '5. TB (cm)',
                'type' => 'number',
                'options' => null,
                'placeholder' => 'cm',
                'suffix' => 'cm',
                'order_index' => 5,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'body_weight_kg',
                'label' => '6. BB (kg)',
                'type' => 'number',
                'options' => null,
                'placeholder' => 'kg',
                'suffix' => 'kg',
                'order_index' => 6,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'is_baligh',
                'label' => '7. Baligh',
                'type' => 'select',
                'options' => ['Belum', 'Sudah'],
                'placeholder' => '- Status -',
                'suffix' => null,
                'order_index' => 7,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_KESANTRIAN,
                'key' => 'kesantrian_notes',
                'label' => '8. Catatan Kesantrian',
                'type' => 'textarea',
                'options' => null,
                'placeholder' => 'Catatan kesantrian...',
                'suffix' => null,
                'order_index' => 8,
                'is_active' => true,
                'is_system' => true,
            ],

            // Akademik
            [
                'module' => self::MODULE_AKADEMIK,
                'key' => 'academic_notes',
                'label' => 'Catatan KBM Kelas',
                'type' => 'textarea',
                'options' => null,
                'placeholder' => 'Catatan perkembangan KBM santri...',
                'suffix' => null,
                'order_index' => 1,
                'is_active' => true,
                'is_system' => true,
            ],

            // Administrasi
            [
                'module' => self::MODULE_ADMINISTRASI,
                'key' => 'last_spp',
                'label' => '1. SPP Terakhir',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Misal: AGUSTUS 2026',
                'suffix' => null,
                'order_index' => 1,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_ADMINISTRASI,
                'key' => 'last_laundry',
                'label' => '2. Laundry Terakhir',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Misal: AGUSTUS 2026',
                'suffix' => null,
                'order_index' => 2,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'module' => self::MODULE_ADMINISTRASI,
                'key' => 'registration_status',
                'label' => '3. Daftar Ulang',
                'type' => 'text',
                'options' => null,
                'placeholder' => 'Lunas / Belum',
                'suffix' => null,
                'order_index' => 3,
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($defaultFields as $item) {
            static::firstOrCreate(
                ['module' => $item['module'], 'key' => $item['key']],
                $item
            );
        }
    }
}
