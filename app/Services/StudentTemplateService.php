<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\HomeroomTeacher;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateService
{
    public const HEADERS = ['NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'Guru BK', 'NIP Wali Kelas'];

    /** Kolom yang diisi manual (indeks dari 0). Jurusan dan Guru BK terisi otomatis oleh rumus. */
    public const INPUT_COLUMNS = [0, 1, 2, 5];

    public const MAX_ROWS = 1500;

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $template = $spreadsheet->getActiveSheet();
        $template->setTitle('Template');

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');

        $reference = $spreadsheet->createSheet();
        $reference->setTitle('Referensi');

        $classEnd = $this->fillReference($reference);
        $this->fillTemplate($template, $classEnd);
        $this->fillGuide($guide);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillTemplate(Worksheet $s, int $classEnd): void
    {
        $digits = config('school.nis_digits');
        $last = self::MAX_ROWS + 1;

        $s->fromArray(self::HEADERS, null, 'A1');
        $s->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension(1)->setRowHeight(24);
        $s->freezePane('A2');

        foreach (['A' => 14, 'B' => 32, 'C' => 18, 'D' => 18, 'E' => 24, 'F' => 24] as $col => $width) {
            $s->getColumnDimension($col)->setWidth($width);
        }

        // Kolom isian berformat Teks (angka nol di depan dan NIP panjang tidak rusak)
        $s->getStyle("A2:C$last")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $s->getStyle("F2:F$last")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // Kolom otomatis diberi warna abu-abu
        $s->getStyle("D2:E$last")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $notes = [
            'A1' => "Masukkan {$digits} digit angka NIS.\nContoh: 2324001\nTanpa spasi, huruf, atau tanda baca.",
            'B1' => "Nama lengkap siswa.\nMaksimal 100 karakter.",
            'C1' => "Pilih dari dropdown (klik sel lalu panah).\nContoh: X RPL 1\nKelas dibuat di menu Kelas & Jurusan.",
            'D1' => "Terisi otomatis dari kelas.\nJangan diisi atau diubah.",
            'E1' => "Terisi otomatis dari kelas.\nJangan diisi atau diubah.",
            'F1' => "NIP wali kelas, angka saja.\nLihat sheet Referensi.",
        ];

        foreach ($notes as $cell => $text) {
            $comment = $s->getComment($cell);
            $comment->setAuthor('Sistem');
            $comment->getText()->createTextRun($text);
            $comment->setWidth('260px');
            $comment->setHeight('100px');
        }

        $lookup = "Referensi!\$A\$2:\$D\${$classEnd}";
        $classRange = "Referensi!\$A\$2:\$A\${$classEnd}";
        $formula = fn (int $row, int $col) => sprintf(
            '=IF($C%1$d="","",IFERROR(VLOOKUP($C%1$d,%2$s,%3$d,FALSE),""))',
            $row, $lookup, $col
        );

        for ($row = 2; $row <= $last; $row++) {
            $s->getCell("C$row")->setDataValidation($this->listValidation($classRange, 'kelas'));
            $s->setCellValue("D$row", $formula($row, 2)); // Jurusan
            $s->setCellValue("E$row", $formula($row, 3)); // Guru BK
        }
    }

    private function fillReference(Worksheet $s): int
    {
        $classrooms = Classroom::with(['grade.bk:id,name,nip', 'major:id,name'])
            ->get()
            ->sortBy(fn ($c) => $c->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $teachers = HomeroomTeacher::orderBy('name')->get(['nip', 'name']);

        $s->fromArray(['Kelas', 'Jurusan', 'Guru BK', 'NIP Guru BK'], null, 'A1');
        $s->fromArray(['NIP Wali Kelas', 'Nama Wali Kelas'], null, 'G1');
        $s->getStyle('A1:H1')->getFont()->setBold(true);

        foreach ($classrooms as $i => $c) {
            $r = $i + 2;
            $this->put($s, "A$r", $c->name);
            $this->put($s, "B$r", $c->major->name);
            $this->put($s, "C$r", $c->grade->bk?->name ?? '-');
            $this->put($s, "D$r", $c->grade->bk?->nip ?? '-');
        }

        foreach ($teachers as $i => $t) {
            $r = $i + 2;
            $this->put($s, "G$r", $t->nip);
            $this->put($s, "H$r", $t->name);
        }

        foreach (range('A', 'H') as $col) {
            $s->getColumnDimension($col)->setAutoSize(true);
        }

        return max(2, $classrooms->count() + 1);
    }

    private function fillGuide(Worksheet $s): void
    {
        $digits = config('school.nis_digits');

        $lines = [
            'PETUNJUK UPDATE MASSAL SISWA',
            '',
            'Isi data pada sheet "Template" mulai baris 2. Jangan mengubah, menghapus, atau menambah kolom pada baris judul (baris 1).',
            "NIS: {$digits} digit angka, tanpa spasi, huruf, atau tanda baca. Contoh: 2324001.",
            'Nama Siswa: nama lengkap, maksimal 100 karakter.',
            'Kelas: pilih dari dropdown, contoh "X RPL 1". Daftar kelas dibuat di menu Kelas & Jurusan.',
            'Jurusan dan Guru BK (kolom abu-abu): terisi otomatis dari kelas. Jangan diisi atau diubah, dan tidak dibaca saat impor.',
            'NIP Wali Kelas: angka saja, harus terdaftar di menu Wali Kelas (lihat sheet Referensi).',
            'Jangan mengubah format kolom. Kolom isian sudah berformat Teks agar angka nol di depan dan NIP yang panjang tidak rusak.',
            'Jangan memakai rumus pada kolom isian. Tempel sebagai nilai (Paste Special > Values).',
            'NIS yang sudah terdaftar akan diperbarui. NIS yang belum terdaftar hanya ditambahkan jika opsi "Tambahkan siswa baru" dicentang saat mengimpor.',
            '',
            'Jika ada satu saja baris yang salah, SELURUH impor dibatalkan dan daftar kesalahannya ditampilkan. Tidak ada data yang berubah.',
            'Gunakan tombol "Periksa file" lebih dulu untuk mengecek isi file tanpa menyimpan.',
            'Maksimal '.self::MAX_ROWS.' baris data per file.',
        ];

        foreach ($lines as $i => $line) {
            $this->put($s, 'A'.($i + 1), $line);
        }

        $s->getColumnDimension('A')->setWidth(120);
        $s->getStyle('A1:A'.count($lines))->getAlignment()->setWrapText(true);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13);
    }

    private function listValidation(string $range, string $field): DataValidation
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Nilai tidak valid');
        $validation->setError("Pilih $field dari daftar.");
        $validation->setFormula1($range);

        return $validation;
    }

    /** Selalu ditulis sebagai teks, supaya NIP/NIS tidak dikonversi jadi angka. */
    private function put(Worksheet $s, string $cell, mixed $value): void
    {
        $s->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }
}