<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan Kelas {{ $classroom->name ?? '' }} - {{ $periodTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            color: #1f2937;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        /* Kop Header */
        .kop-logo { max-height: 52px; display: block; margin: 0 auto 4px auto; }
        .kop-title { font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; }
        .kop-inst { font-size: 11.5pt; font-weight: bold; margin-top: 2px; }
        .kop-city { font-size: 10.5pt; font-weight: bold; margin-top: 1px; }
        .kop-divider { border-bottom: 2.5px double #1f2937; margin: 6px 0 8px 0; }
        
        /* Period Badge */
        .badge-period {
            display: inline-block;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 2px 14px;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* Bio Table */
        .table-bio { width: 100%; margin-bottom: 8px; border-collapse: collapse; }
        .table-bio td { padding: 1.5px 0; vertical-align: top; }

        /* Section Bar */
        .section-bar {
            background-color: {{ $institution->accent_color ?? '#059669' }};
            color: #ffffff;
            font-weight: bold;
            font-size: 9pt;
            padding: 2.5px 6px;
            margin-top: 6px;
            margin-bottom: 4px;
        }

        /* Content Table */
        .table-content { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .table-content td { vertical-align: top; padding: 1.5px 0; font-size: 8.5pt; }
        .col-label { width: 135pt; }
        .col-colon { width: 10pt; text-align: center; }
        .col-value { width: auto; }

        /* Footer / Signature */
        .footer-table { width: 100%; margin-top: 10px; border-collapse: collapse; page-break-inside: avoid; }
        .sign-box { width: 200pt; text-align: center; float: right; }
        .sign-area { position: relative; height: 55px; margin: 2px 0; }
        .sign-stamp { position: absolute; left: 15px; top: -5px; width: 70px; opacity: 0.85; }
        .sign-signature { position: absolute; right: 25px; top: 0; width: 95px; }
    </style>
</head>
<body>

@php
    $logoImage = $institution->imageDataUri('logo_path');
    $stampImage = $institution->imageDataUri('stamp_path');
    $signatureImage = $institution->imageDataUri('signature_path');
@endphp
@foreach($reports as $report)
    @php
        $student = $report->student;
        $record = $report->record;
    @endphp

    @php
        $tahfidzFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_TAHFIDZ);
        $kesantrianFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_KESANTRIAN);
        $academicFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_AKADEMIK);
        $adminFields = \App\Models\ModuleField::getActiveFields(\App\Models\ModuleField::MODULE_ADMINISTRASI);
    @endphp

    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        <!-- Header / Kop -->
        <div class="text-center">
            @if($logoImage)
                <img src="{{ $logoImage }}" class="kop-logo">
            @endif
            <div class="kop-title">LAPORAN BULANAN</div>
            <div class="kop-inst uppercase">{{ $institution->name }}</div>
            <div class="kop-city uppercase">{{ $institution->city }}</div>
            <div class="kop-divider"></div>
            <div class="badge-period uppercase">BULAN : {{ $report->period_title }}</div>
        </div>

        <!-- Biodata Santri -->
        <table class="table-bio">
            <tr>
                <td style="width: 75pt;" class="text-bold">Nama {{ \App\Models\Institution::term('student') }}</td>
                <td style="width: 10pt;">:</td>
                <td class="text-bold">{{ $student->name }}</td>
            </tr>
            <tr>
                <td class="text-bold">NIS/ {{ \App\Models\Institution::term('class') }}</td>
                <td>:</td>
                <td>{{ $student->nis }} &nbsp;/&nbsp; {{ $student->classroom->name ?? '-' }}</td>
            </tr>
        </table>

        <!-- A. TAHFIDZ -->
        <div class="section-bar">A. TAHFIDZ</div>
        <table class="table-content">
            @forelse($tahfidzFields as $index => $fld)
            <tr>
                <td class="col-label">{{ $index + 1 }}. {{ $fld->label }}</td>
                <td class="col-colon">:</td>
                <td class="col-value">
                    @if($fld->type === 'textarea')
                        {!! nl2br(e($record->getFieldValue($fld->key) ?? '-')) !!}
                    @else
                        {{ $record->getFieldValue($fld->key) ?? '-' }}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="col-value" style="font-style: italic; color: #9ca3af;">Tidak ada data penilaian tahfidz.</td>
            </tr>
            @endforelse
        </table>

        <!-- B. KESANTRIAN -->
        <div class="section-bar">B. KESANTRIAN</div>
        <table class="table-content">
            @forelse($kesantrianFields as $index => $fld)
            <tr>
                <td class="col-label">{{ $index + 1 }}. {{ $fld->label }}</td>
                <td class="col-colon">:</td>
                <td class="col-value">
                    @php
                        $val = $record->getFieldValue($fld->key);
                        if ($fld->key === 'body_height_cm' && $val) {
                            $val .= ' Cm';
                        } elseif ($fld->key === 'body_weight_kg' && $val) {
                            $val .= ' Kg';
                        }
                    @endphp
                    @if($fld->type === 'textarea')
                        {!! nl2br(e($val ?? '-')) !!}
                    @else
                        {{ $val ?? '-' }}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="col-value" style="font-style: italic; color: #9ca3af;">Tidak ada data penilaian kesantrian.</td>
            </tr>
            @endforelse
        </table>

        <!-- C. AKADEMIK -->
        <div class="section-bar">C. AKADEMIK</div>
        <table class="table-content">
            @forelse($academicFields as $index => $fld)
            <tr>
                <td class="col-label">{{ $index + 1 }}. {{ $fld->label }}</td>
                <td class="col-colon">:</td>
                <td class="col-value">
                    @if($fld->type === 'textarea')
                        {!! nl2br(e($record->getFieldValue($fld->key) ?? '-')) !!}
                    @else
                        {{ $record->getFieldValue($fld->key) ?? '-' }}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="col-value" style="font-style: italic; color: #9ca3af;">Tidak ada data penilaian akademik.</td>
            </tr>
            @endforelse
        </table>

        <!-- D. ADMINISTRASI -->
        <div class="section-bar">D. ADMINISTRASI Per Tanggal {{ \Carbon\Carbon::parse($report->cutoff_date)->translatedFormat('d F Y') }}</div>
        <table class="table-content">
            @forelse($adminFields as $index => $fld)
            <tr>
                <td class="col-label">{{ $index + 1 }}. {{ $fld->label }}</td>
                <td class="col-colon">:</td>
                <td class="col-value">
                    @if($fld->type === 'textarea')
                        {!! nl2br(e($record->getFieldValue($fld->key) ?? '-')) !!}
                    @else
                        {{ $record->getFieldValue($fld->key) ?? '-' }}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="col-value" style="font-style: italic; color: #9ca3af;">Tidak ada data penilaian administrasi.</td>
            </tr>
            @endforelse
        </table>

        <!-- Footer Pengesahan -->
        <table class="footer-table">
            <tr>
                <td></td>
                <td style="width: 210pt;">
                    <div class="sign-box">
                        <div>{{ $institution->city }}, {{ \Carbon\Carbon::parse($report->report_date)->translatedFormat('d F Y') }}</div>
                        <div style="margin-top: 1px;">{{ $institution->director_title }}</div>
                        
                        <div class="sign-area">
                            @if($stampImage)
                                <img src="{{ $stampImage }}" class="sign-stamp">
                            @endif
                            @if($signatureImage)
                                <img src="{{ $signatureImage }}" class="sign-signature">
                            @endif
                        </div>
                        
                        <div class="text-bold" style="text-decoration: underline;">{{ $institution->director_name }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>

