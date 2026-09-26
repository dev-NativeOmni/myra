<?php

namespace App\Helpers;

class SurahHelper
{
    /**
     * Get list of all 114 Surahs with metadata.
     *
     * @return array<int, array{number: int, name_latin: string, name_ar: string, total_ayah: int, juz_start: int, juz_end: int}>
     */
    public static function all(): array
    {
        return [
            1 => ['number' => 1, 'name_latin' => 'Al-Fatihah', 'name_ar' => 'الفاتحة', 'total_ayah' => 7, 'juz_start' => 1, 'juz_end' => 1],
            2 => ['number' => 2, 'name_latin' => 'Al-Baqarah', 'name_ar' => 'البقرة', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3],
            3 => ['number' => 3, 'name_latin' => "Ali 'Imran", 'name_ar' => 'آل عمران', 'total_ayah' => 200, 'juz_start' => 3, 'juz_end' => 4],
            4 => ['number' => 4, 'name_latin' => 'An-Nisa', 'name_ar' => 'النساء', 'total_ayah' => 176, 'juz_start' => 4, 'juz_end' => 6],
            5 => ['number' => 5, 'name_latin' => "Al-Ma'idah", 'name_ar' => 'المائدة', 'total_ayah' => 120, 'juz_start' => 6, 'juz_end' => 7],
            6 => ['number' => 6, 'name_latin' => "Al-An'am", 'name_ar' => 'الأنعام', 'total_ayah' => 165, 'juz_start' => 7, 'juz_end' => 8],
            7 => ['number' => 7, 'name_latin' => "Al-A'raf", 'name_ar' => 'الأعراف', 'total_ayah' => 206, 'juz_start' => 8, 'juz_end' => 9],
            8 => ['number' => 8, 'name_latin' => 'Al-Anfal', 'name_ar' => 'الأنفال', 'total_ayah' => 75, 'juz_start' => 9, 'juz_end' => 10],
            9 => ['number' => 9, 'name_latin' => 'At-Taubah', 'name_ar' => 'التوبة', 'total_ayah' => 129, 'juz_start' => 10, 'juz_end' => 11],
            10 => ['number' => 10, 'name_latin' => 'Yunus', 'name_ar' => 'يونس', 'total_ayah' => 109, 'juz_start' => 11, 'juz_end' => 11],
            11 => ['number' => 11, 'name_latin' => 'Hud', 'name_ar' => 'هود', 'total_ayah' => 123, 'juz_start' => 11, 'juz_end' => 12],
            12 => ['number' => 12, 'name_latin' => 'Yusuf', 'name_ar' => 'يوسف', 'total_ayah' => 111, 'juz_start' => 12, 'juz_end' => 13],
            13 => ['number' => 13, 'name_latin' => "Ar-Ra'd", 'name_ar' => 'الرعد', 'total_ayah' => 43, 'juz_start' => 13, 'juz_end' => 13],
            14 => ['number' => 14, 'name_latin' => 'Ibrahim', 'name_ar' => 'إبراهيم', 'total_ayah' => 52, 'juz_start' => 13, 'juz_end' => 13],
            15 => ['number' => 15, 'name_latin' => 'Al-Hijr', 'name_ar' => 'الحجر', 'total_ayah' => 99, 'juz_start' => 14, 'juz_end' => 14],
            16 => ['number' => 16, 'name_latin' => 'An-Nahl', 'name_ar' => 'النحل', 'total_ayah' => 128, 'juz_start' => 14, 'juz_end' => 14],
            17 => ['number' => 17, 'name_latin' => 'Al-Isra', 'name_ar' => 'الإسراء', 'total_ayah' => 111, 'juz_start' => 15, 'juz_end' => 15],
            18 => ['number' => 18, 'name_latin' => 'Al-Kahf', 'name_ar' => 'الكهف', 'total_ayah' => 110, 'juz_start' => 15, 'juz_end' => 16],
            19 => ['number' => 19, 'name_latin' => 'Maryam', 'name_ar' => 'مريم', 'total_ayah' => 98, 'juz_start' => 16, 'juz_end' => 16],
            20 => ['number' => 20, 'name_latin' => 'Taha', 'name_ar' => 'طه', 'total_ayah' => 135, 'juz_start' => 16, 'juz_end' => 16],
            21 => ['number' => 21, 'name_latin' => 'Al-Anbiya', 'name_ar' => 'الأنبياء', 'total_ayah' => 112, 'juz_start' => 17, 'juz_end' => 17],
            22 => ['number' => 22, 'name_latin' => 'Al-Hajj', 'name_ar' => 'الحج', 'total_ayah' => 78, 'juz_start' => 17, 'juz_end' => 17],
            23 => ['number' => 23, 'name_latin' => "Al-Mu'minun", 'name_ar' => 'المؤمنون', 'total_ayah' => 118, 'juz_start' => 18, 'juz_end' => 18],
            24 => ['number' => 24, 'name_latin' => 'An-Nur', 'name_ar' => 'النور', 'total_ayah' => 64, 'juz_start' => 18, 'juz_end' => 18],
            25 => ['number' => 25, 'name_latin' => 'Al-Furqan', 'name_ar' => 'الفرقان', 'total_ayah' => 77, 'juz_start' => 18, 'juz_end' => 19],
            26 => ['number' => 26, 'name_latin' => "Ash-Shu'ara", 'name_ar' => 'الشعراء', 'total_ayah' => 227, 'juz_start' => 19, 'juz_end' => 19],
            27 => ['number' => 27, 'name_latin' => 'An-Naml', 'name_ar' => 'النمل', 'total_ayah' => 93, 'juz_start' => 19, 'juz_end' => 20],
            28 => ['number' => 28, 'name_latin' => 'Al-Qasas', 'name_ar' => 'القصص', 'total_ayah' => 88, 'juz_start' => 20, 'juz_end' => 20],
            29 => ['number' => 29, 'name_latin' => "Al-'Ankabut", 'name_ar' => 'العنكبوت', 'total_ayah' => 69, 'juz_start' => 20, 'juz_end' => 21],
            30 => ['number' => 30, 'name_latin' => 'Ar-Rum', 'name_ar' => 'الروم', 'total_ayah' => 60, 'juz_start' => 21, 'juz_end' => 21],
            31 => ['number' => 31, 'name_latin' => 'Luqman', 'name_ar' => 'لقمان', 'total_ayah' => 34, 'juz_start' => 21, 'juz_end' => 21],
            32 => ['number' => 32, 'name_latin' => 'As-Sajdah', 'name_ar' => 'السجدة', 'total_ayah' => 30, 'juz_start' => 21, 'juz_end' => 21],
            33 => ['number' => 33, 'name_latin' => 'Al-Ahzab', 'name_ar' => 'الأحزاب', 'total_ayah' => 73, 'juz_start' => 21, 'juz_end' => 22],
            34 => ['number' => 34, 'name_latin' => 'Saba', 'name_ar' => 'سبأ', 'total_ayah' => 54, 'juz_start' => 22, 'juz_end' => 22],
            35 => ['number' => 35, 'name_latin' => 'Fatir', 'name_ar' => 'فاطر', 'total_ayah' => 45, 'juz_start' => 22, 'juz_end' => 22],
            36 => ['number' => 36, 'name_latin' => 'Ya-Sin', 'name_ar' => 'يس', 'total_ayah' => 83, 'juz_start' => 22, 'juz_end' => 23],
            37 => ['number' => 37, 'name_latin' => 'As-Saffat', 'name_ar' => 'الصافات', 'total_ayah' => 182, 'juz_start' => 23, 'juz_end' => 23],
            38 => ['number' => 38, 'name_latin' => 'Sad', 'name_ar' => 'ص', 'total_ayah' => 88, 'juz_start' => 23, 'juz_end' => 23],
            39 => ['number' => 39, 'name_latin' => 'Az-Zumar', 'name_ar' => 'الزمر', 'total_ayah' => 75, 'juz_start' => 23, 'juz_end' => 24],
            40 => ['number' => 40, 'name_latin' => 'Ghafir', 'name_ar' => 'غافر', 'total_ayah' => 85, 'juz_start' => 24, 'juz_end' => 24],
            41 => ['number' => 41, 'name_latin' => 'Fussilat', 'name_ar' => 'فصلت', 'total_ayah' => 54, 'juz_start' => 24, 'juz_end' => 25],
            42 => ['number' => 42, 'name_latin' => 'Ash-Shura', 'name_ar' => 'الشورى', 'total_ayah' => 53, 'juz_start' => 25, 'juz_end' => 25],
            43 => ['number' => 43, 'name_latin' => 'Az-Zukhruf', 'name_ar' => 'الزخرف', 'total_ayah' => 89, 'juz_start' => 25, 'juz_end' => 25],
            44 => ['number' => 44, 'name_latin' => 'Ad-Dukhan', 'name_ar' => 'الدخان', 'total_ayah' => 59, 'juz_start' => 25, 'juz_end' => 25],
            45 => ['number' => 45, 'name_latin' => 'Al-Jathiyah', 'name_ar' => 'الجاثية', 'total_ayah' => 37, 'juz_start' => 25, 'juz_end' => 25],
            46 => ['number' => 46, 'name_latin' => 'Al-Ahqaf', 'name_ar' => 'الأحقاف', 'total_ayah' => 35, 'juz_start' => 26, 'juz_end' => 26],
            47 => ['number' => 47, 'name_latin' => 'Muhammad', 'name_ar' => 'محمد', 'total_ayah' => 38, 'juz_start' => 26, 'juz_end' => 26],
            48 => ['number' => 48, 'name_latin' => 'Al-Fath', 'name_ar' => 'الفتح', 'total_ayah' => 29, 'juz_start' => 26, 'juz_end' => 26],
            49 => ['number' => 49, 'name_latin' => 'Al-Hujurat', 'name_ar' => 'الحجرات', 'total_ayah' => 18, 'juz_start' => 26, 'juz_end' => 26],
            50 => ['number' => 50, 'name_latin' => 'Qaf', 'name_ar' => 'ق', 'total_ayah' => 45, 'juz_start' => 26, 'juz_end' => 26],
            51 => ['number' => 51, 'name_latin' => 'Adh-Dhariyat', 'name_ar' => 'الذاريات', 'total_ayah' => 60, 'juz_start' => 26, 'juz_end' => 27],
            52 => ['number' => 52, 'name_latin' => 'At-Tur', 'name_ar' => 'الطور', 'total_ayah' => 49, 'juz_start' => 27, 'juz_end' => 27],
            53 => ['number' => 53, 'name_latin' => 'An-Najm', 'name_ar' => 'النجم', 'total_ayah' => 62, 'juz_start' => 27, 'juz_end' => 27],
            54 => ['number' => 54, 'name_latin' => 'Al-Qamar', 'name_ar' => 'القمر', 'total_ayah' => 55, 'juz_start' => 27, 'juz_end' => 27],
            55 => ['number' => 55, 'name_latin' => 'Ar-Rahman', 'name_ar' => 'الرحمن', 'total_ayah' => 78, 'juz_start' => 27, 'juz_end' => 27],
            56 => ['number' => 56, 'name_latin' => "Al-Waqi'ah", 'name_ar' => 'الواقعة', 'total_ayah' => 96, 'juz_start' => 27, 'juz_end' => 27],
            57 => ['number' => 57, 'name_latin' => 'Al-Hadid', 'name_ar' => 'الحديد', 'total_ayah' => 29, 'juz_start' => 27, 'juz_end' => 28],
            58 => ['number' => 58, 'name_latin' => 'Al-Mujadilah', 'name_ar' => 'المجادلة', 'total_ayah' => 22, 'juz_start' => 28, 'juz_end' => 28],
            59 => ['number' => 59, 'name_latin' => 'Al-Hashr', 'name_ar' => 'الحشر', 'total_ayah' => 24, 'juz_start' => 28, 'juz_end' => 28],
            60 => ['number' => 60, 'name_latin' => 'Al-Mumtahanah', 'name_ar' => 'الممتحنة', 'total_ayah' => 13, 'juz_start' => 28, 'juz_end' => 28],
            61 => ['number' => 61, 'name_latin' => 'As-Saff', 'name_ar' => 'الصف', 'total_ayah' => 14, 'juz_start' => 28, 'juz_end' => 28],
            62 => ['number' => 62, 'name_latin' => "Al-Jumu'ah", 'name_ar' => 'الجمعة', 'total_ayah' => 11, 'juz_start' => 28, 'juz_end' => 28],
            63 => ['number' => 63, 'name_latin' => 'Al-Munafiqun', 'name_ar' => 'المنافقون', 'total_ayah' => 11, 'juz_start' => 28, 'juz_end' => 28],
            64 => ['number' => 64, 'name_latin' => 'At-Taghabun', 'name_ar' => 'التغابن', 'total_ayah' => 18, 'juz_start' => 28, 'juz_end' => 28],
            65 => ['number' => 65, 'name_latin' => 'At-Talaq', 'name_ar' => 'الطلاق', 'total_ayah' => 12, 'juz_start' => 28, 'juz_end' => 28],
            66 => ['number' => 66, 'name_latin' => 'At-Tahrim', 'name_ar' => 'التحريم', 'total_ayah' => 12, 'juz_start' => 28, 'juz_end' => 28],
            67 => ['number' => 67, 'name_latin' => 'Al-Mulk', 'name_ar' => 'الملك', 'total_ayah' => 30, 'juz_start' => 29, 'juz_end' => 29],
            68 => ['number' => 68, 'name_latin' => 'Al-Qalam', 'name_ar' => 'القلم', 'total_ayah' => 52, 'juz_start' => 29, 'juz_end' => 29],
            69 => ['number' => 69, 'name_latin' => 'Al-Haqqah', 'name_ar' => 'الحاقة', 'total_ayah' => 52, 'juz_start' => 29, 'juz_end' => 29],
            70 => ['number' => 70, 'name_latin' => "Al-Ma'arij", 'name_ar' => 'المعارج', 'total_ayah' => 44, 'juz_start' => 29, 'juz_end' => 29],
            71 => ['number' => 71, 'name_latin' => 'Nuh', 'name_ar' => 'نوح', 'total_ayah' => 28, 'juz_start' => 29, 'juz_end' => 29],
            72 => ['number' => 72, 'name_latin' => 'Al-Jinn', 'name_ar' => 'الجن', 'total_ayah' => 28, 'juz_start' => 29, 'juz_end' => 29],
            73 => ['number' => 73, 'name_latin' => 'Al-Muzzammil', 'name_ar' => 'المزمل', 'total_ayah' => 20, 'juz_start' => 29, 'juz_end' => 29],
            74 => ['number' => 74, 'name_latin' => 'Al-Muddaththir', 'name_ar' => 'المدثر', 'total_ayah' => 56, 'juz_start' => 29, 'juz_end' => 29],
            75 => ['number' => 75, 'name_latin' => 'Al-Qiyamah', 'name_ar' => 'القيامة', 'total_ayah' => 40, 'juz_start' => 29, 'juz_end' => 29],
            76 => ['number' => 76, 'name_latin' => 'Al-Insan', 'name_ar' => 'الإنسان', 'total_ayah' => 31, 'juz_start' => 29, 'juz_end' => 29],
            77 => ['number' => 77, 'name_latin' => 'Al-Mursalat', 'name_ar' => 'المرسلات', 'total_ayah' => 50, 'juz_start' => 29, 'juz_end' => 29],
            78 => ['number' => 78, 'name_latin' => "An-Naba'", 'name_ar' => 'النبأ', 'total_ayah' => 40, 'juz_start' => 30, 'juz_end' => 30],
            79 => ['number' => 79, 'name_latin' => "An-Nazi'at", 'name_ar' => 'النازعات', 'total_ayah' => 46, 'juz_start' => 30, 'juz_end' => 30],
            80 => ['number' => 80, 'name_latin' => "'Abasa", 'name_ar' => 'عبس', 'total_ayah' => 42, 'juz_start' => 30, 'juz_end' => 30],
            81 => ['number' => 81, 'name_latin' => 'At-Takwir', 'name_ar' => 'التكوير', 'total_ayah' => 29, 'juz_start' => 30, 'juz_end' => 30],
            82 => ['number' => 82, 'name_latin' => 'Al-Infitar', 'name_ar' => 'الانفطار', 'total_ayah' => 19, 'juz_start' => 30, 'juz_end' => 30],
            83 => ['number' => 83, 'name_latin' => 'Al-Mutaffifin', 'name_ar' => 'المطففين', 'total_ayah' => 36, 'juz_start' => 30, 'juz_end' => 30],
            84 => ['number' => 84, 'name_latin' => 'Al-Inshiqaq', 'name_ar' => 'الانشقاق', 'total_ayah' => 25, 'juz_start' => 30, 'juz_end' => 30],
            85 => ['number' => 85, 'name_latin' => 'Al-Buruj', 'name_ar' => 'البروج', 'total_ayah' => 22, 'juz_start' => 30, 'juz_end' => 30],
            86 => ['number' => 86, 'name_latin' => 'At-Tariq', 'name_ar' => 'الطارق', 'total_ayah' => 17, 'juz_start' => 30, 'juz_end' => 30],
            87 => ['number' => 87, 'name_latin' => "Al-A'la", 'name_ar' => 'الأعلى', 'total_ayah' => 19, 'juz_start' => 30, 'juz_end' => 30],
            88 => ['number' => 88, 'name_latin' => 'Al-Ghashiyah', 'name_ar' => 'الغاشية', 'total_ayah' => 26, 'juz_start' => 30, 'juz_end' => 30],
            89 => ['number' => 89, 'name_latin' => 'Al-Fajr', 'name_ar' => 'الفجر', 'total_ayah' => 30, 'juz_start' => 30, 'juz_end' => 30],
            90 => ['number' => 90, 'name_latin' => 'Al-Balad', 'name_ar' => 'البلد', 'total_ayah' => 20, 'juz_start' => 30, 'juz_end' => 30],
            91 => ['number' => 91, 'name_latin' => 'Ash-Shams', 'name_ar' => 'الشمس', 'total_ayah' => 15, 'juz_start' => 30, 'juz_end' => 30],
            92 => ['number' => 92, 'name_latin' => 'Al-Lail', 'name_ar' => 'الليل', 'total_ayah' => 21, 'juz_start' => 30, 'juz_end' => 30],
            93 => ['number' => 93, 'name_latin' => 'Ad-Duha', 'name_ar' => 'الضحى', 'total_ayah' => 11, 'juz_start' => 30, 'juz_end' => 30],
            94 => ['number' => 94, 'name_latin' => 'Ash-Sharh', 'name_ar' => 'الشرح', 'total_ayah' => 8, 'juz_start' => 30, 'juz_end' => 30],
            95 => ['number' => 95, 'name_latin' => 'At-Tin', 'name_ar' => 'التين', 'total_ayah' => 8, 'juz_start' => 30, 'juz_end' => 30],
            96 => ['number' => 96, 'name_latin' => "Al-'Alaq", 'name_ar' => 'العلق', 'total_ayah' => 19, 'juz_start' => 30, 'juz_end' => 30],
            97 => ['number' => 97, 'name_latin' => 'Al-Qadr', 'name_ar' => 'القدر', 'total_ayah' => 5, 'juz_start' => 30, 'juz_end' => 30],
            98 => ['number' => 98, 'name_latin' => 'Al-Bayyinah', 'name_ar' => 'البينة', 'total_ayah' => 8, 'juz_start' => 30, 'juz_end' => 30],
            99 => ['number' => 99, 'name_latin' => 'Az-Zalzalah', 'name_ar' => 'الزلزلة', 'total_ayah' => 8, 'juz_start' => 30, 'juz_end' => 30],
            100 => ['number' => 100, 'name_latin' => "Al-'Adiyat", 'name_ar' => 'العاديات', 'total_ayah' => 11, 'juz_start' => 30, 'juz_end' => 30],
            101 => ['number' => 101, 'name_latin' => "Al-Qari'ah", 'name_ar' => 'القارعة', 'total_ayah' => 11, 'juz_start' => 30, 'juz_end' => 30],
            102 => ['number' => 102, 'name_latin' => 'At-Takathur', 'name_ar' => 'التكاثر', 'total_ayah' => 8, 'juz_start' => 30, 'juz_end' => 30],
            103 => ['number' => 103, 'name_latin' => "Al-'Asr", 'name_ar' => 'العصر', 'total_ayah' => 3, 'juz_start' => 30, 'juz_end' => 30],
            104 => ['number' => 104, 'name_latin' => 'Al-Humazah', 'name_ar' => 'الهمزة', 'total_ayah' => 9, 'juz_start' => 30, 'juz_end' => 30],
            105 => ['number' => 105, 'name_latin' => 'Al-Fil', 'name_ar' => 'الفيل', 'total_ayah' => 5, 'juz_start' => 30, 'juz_end' => 30],
            106 => ['number' => 106, 'name_latin' => 'Quraish', 'name_ar' => 'قريش', 'total_ayah' => 4, 'juz_start' => 30, 'juz_end' => 30],
            107 => ['number' => 107, 'name_latin' => "Al-Ma'un", 'name_ar' => 'الماعون', 'total_ayah' => 7, 'juz_start' => 30, 'juz_end' => 30],
            108 => ['number' => 108, 'name_latin' => 'Al-Kauthar', 'name_ar' => 'الكوثر', 'total_ayah' => 3, 'juz_start' => 30, 'juz_end' => 30],
            109 => ['number' => 109, 'name_latin' => 'Al-Kafirun', 'name_ar' => 'الكافرون', 'total_ayah' => 6, 'juz_start' => 30, 'juz_end' => 30],
            110 => ['number' => 110, 'name_latin' => 'An-Nasr', 'name_ar' => 'النصر', 'total_ayah' => 3, 'juz_start' => 30, 'juz_end' => 30],
            111 => ['number' => 111, 'name_latin' => 'Al-Lahab', 'name_ar' => 'المسد', 'total_ayah' => 5, 'juz_start' => 30, 'juz_end' => 30],
            112 => ['number' => 112, 'name_latin' => 'Al-Ikhlas', 'name_ar' => 'الإخلاص', 'total_ayah' => 4, 'juz_start' => 30, 'juz_end' => 30],
            113 => ['number' => 113, 'name_latin' => 'Al-Falaq', 'name_ar' => 'الفلق', 'total_ayah' => 5, 'juz_start' => 30, 'juz_end' => 30],
            114 => ['number' => 114, 'name_latin' => 'An-Nas', 'name_ar' => 'الناس', 'total_ayah' => 6, 'juz_start' => 30, 'juz_end' => 30],
        ];
    }

    /**
     * Find surah by number or ID.
     *
     * @return array{number: int, name_latin: string, name_ar: string, total_ayah: int, juz_start: int, juz_end: int}|null
     */
    public static function find(int $number): ?array
    {
        $all = self::all();
        return $all[$number] ?? null;
    }

    /**
     * Find surah by Latin name (case-insensitive).
     *
     * @return array{number: int, name_latin: string, name_ar: string, total_ayah: int, juz_start: int, juz_end: int}|null
     */
    public static function findByName(string $name): ?array
    {
        $cleanName = strtolower(trim($name));
        foreach (self::all() as $surah) {
            if (strtolower($surah['name_latin']) === $cleanName) {
                return $surah;
            }
        }
        return null;
    }
}

