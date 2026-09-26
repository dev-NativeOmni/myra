<?php

namespace App\Services;

use App\Models\Student;

class StudentProgressService
{
    /**
     * Parse score from predicate string (e.g. "A (Sangat Baik)" => 4, "B" => 3).
     */
    public static function predicateToScore(?string $predicate): ?float
    {
        if (! $predicate) {
            return null;
        }

        $trimmed = strtoupper(trim($predicate));
        if (str_starts_with($trimmed, 'A')) {
            return 4.0;
        }
        if (str_starts_with($trimmed, 'B')) {
            return 3.0;
        }
        if (str_starts_with($trimmed, 'C')) {
            return 2.0;
        }
        if (str_starts_with($trimmed, 'D')) {
            return 1.0;
        }

        return null;
    }

    /**
     * Extract numeric juz count from text (e.g. "30 Juz", "Juz 28, 29, 30", "5").
     */
    public static function parseJuzNumber(?string $text): ?float
    {
        if (! $text) {
            return null;
        }

        // Match patterns like "30 Juz" or "30"
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:juz|halaman|hal)?/i', $text, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Generate structured progress trend data for a student across all monthly reports.
     */
    public static function getStudentTrends(Student $student): array
    {
        $reports = $student->monthlyReports()
            ->with('record')
            ->orderBy('report_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $labels = [];
        $tahfidzJuz = [];
        $tahfidzNotes = [];
        $adabIbadah = [];
        $adabAkhlak = [];
        $adabKerapian = [];
        $adabDisiplin = [];
        $adabAverage = [];
        $bodyHeight = [];
        $bodyWeight = [];
        $bmiList = [];

        foreach ($reports as $report) {
            $record = $report->record;
            $labels[] = $report->period_title;

            // 1. Tahfidz data
            $rawAkumulasi = $record?->getFieldValue('tahfidz_akumulasi');
            $rawSetoran = $record?->getFieldValue('tahfidz_setoran');
            $juzVal = self::parseJuzNumber($rawAkumulasi) ?? self::parseJuzNumber($rawSetoran);
            $tahfidzJuz[] = $juzVal;
            $tahfidzNotes[] = $record?->getFieldValue('tahfidz_notes') ?? '-';

            // 2. Adab & Character data
            $ibadahScore = self::predicateToScore($record?->getFieldValue('adab_ibadah'));
            $akhlakScore = self::predicateToScore($record?->getFieldValue('adab_akhlak'));
            $kerapianScore = self::predicateToScore($record?->getFieldValue('adab_kerapian'));
            $disiplinScore = self::predicateToScore($record?->getFieldValue('adab_kedisiplinan'));

            $adabIbadah[] = $ibadahScore;
            $adabAkhlak[] = $akhlakScore;
            $adabKerapian[] = $kerapianScore;
            $adabDisiplin[] = $disiplinScore;

            $validScores = array_filter([$ibadahScore, $akhlakScore, $kerapianScore, $disiplinScore], fn ($v) => $v !== null);
            $avgScore = count($validScores) > 0 ? round(array_sum($validScores) / count($validScores), 2) : null;
            $adabAverage[] = $avgScore;

            // 3. Physical Growth data
            $h = $record?->getFieldValue('body_height_cm') ? (float) $record->getFieldValue('body_height_cm') : null;
            $w = $record?->getFieldValue('body_weight_kg') ? (float) $record->getFieldValue('body_weight_kg') : null;
            $bodyHeight[] = $h;
            $bodyWeight[] = $w;

            if ($h && $w && $h > 0) {
                $heightM = $h / 100;
                $bmi = round($w / ($heightM * $heightM), 1);
                $bmiList[] = $bmi;
            } else {
                $bmiList[] = null;
            }
        }

        return [
            'has_data' => count($labels) > 0,
            'total_periods' => count($labels),
            'labels' => $labels,
            'tahfidz' => [
                'juz' => $tahfidzJuz,
                'notes' => $tahfidzNotes,
                'latest' => end($tahfidzJuz) ?: null,
            ],
            'adab' => [
                'ibadah' => $adabIbadah,
                'akhlak' => $adabAkhlak,
                'kerapian' => $adabKerapian,
                'disiplin' => $adabDisiplin,
                'average' => $adabAverage,
                'latest_avg' => end($adabAverage) ?: null,
            ],
            'physical' => [
                'height' => $bodyHeight,
                'weight' => $bodyWeight,
                'bmi' => $bmiList,
                'latest_height' => end($bodyHeight) ?: null,
                'latest_weight' => end($bodyWeight) ?: null,
                'latest_bmi' => end($bmiList) ?: null,
            ],
        ];
    }
}
