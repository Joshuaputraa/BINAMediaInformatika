@php
    $school = 'BINA Media Informatika';

    $logoPath = public_path('images/metiklogo.png');
    $logo = file_exists($logoPath)
        ? 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $fontRegular = str_replace('\\', '/', public_path('fonts/Inter-Regular.ttf'));
    $fontBold = str_replace('\\', '/', public_path('fonts/Inter-Bold.ttf'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penanganan Siswa</title>
    <style>
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: normal;
            src: url('{{ $fontRegular }}') format('truetype');
        }
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: bold;
            src: url('{{ $fontBold }}') format('truetype');
        }

        body { font-family: 'Inter', sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 2px; color: #1d4ed8; }
        p { margin: 0; }
        .muted { color: #6b7280; }

        table.header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1d4ed8; margin-bottom: 10px; }
        table.header td { padding: 0 0 8px; vertical-align: middle; }
        table.header td.logo { width: 64px; }
        table.header img { height: 52px; }
        .school { font-weight: bold; margin-bottom: 2px; }

        .summary { margin: 10px 0; padding: 8px; background: #eff6ff; }
        .summary span { margin-right: 14px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { text-align: left; padding: 6px; background: #1d4ed8; color: #fff; }
        table.data td { padding: 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }

        .footer { margin-top: 14px; color: #9ca3af; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if ($logo)
                <td class="logo"><img src="{{ $logo }}" alt="Logo"></td>
            @endif
            <td>
                <h1>Laporan Penanganan Siswa</h1>
                <p class="school">{{ $school }}</p>
                <p class="muted">Periode: {{ $periode }}@if ($typeLabel) &middot; Tindakan: {{ $typeLabel }}@endif</p>
            </td>
        </tr>
    </table>

    <div class="summary">
        <span>Siswa tercatat: <strong>{{ $summary['students'] }}</strong></span>
        <span>Total penanganan: <strong>{{ $summary['total'] }}</strong></span>
        @foreach ($summary['byType'] as $type => $count)
            <span>{{ \App\Enums\StudentStatus::from($type)->label() }}: <strong>{{ $count }}</strong></span>
        @endforeach
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Tindakan</th>
                <th>Wali Kelas</th>
                <th>Guru BK</th>
                <th>Dicatat oleh</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $r->created_at->format('d/m/Y') }}</td>
                    <td>{{ $r->student?->nis }}</td>
                    <td>{{ $r->student?->name }}</td>
                    <td>{{ $r->type->label() }}</td>
                    <td>{{ $r->homeroomName() ?? '-' }}</td>
                    <td>{{ $r->bkName() ?? '-' }}</td>
                    <td>{{ $r->user?->name }}</td>
                    <td>{{ $r->notes }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Tidak ada data pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>