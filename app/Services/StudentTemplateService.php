<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\HomeroomTeacher;
use App\Models\Major;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateService
{
    public const HEADERS = ['NIS', 'Nama Siswa', 'Kelas', 'Jurusan', 'NIP Guru BK', 'NIP Wali Kelas'];

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

        [$gradeEnd, $majorEnd] = $this->fillReference($reference);
        $this->fillTemplate($template, $gradeEnd, $majorEnd);
        $this->fillGuide($guide);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillTemplate(Worksheet $s, int $gradeEnd, int $majorEnd): void
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

        foreach (['A' => 14, 'B' => 32, 'C' => 14, 'D' => 22, 'E' => 24, 'F' => 24] as $col => $width) {
            $s->getColumnDimension($col)->setWidth($width);
        }

        // Semua kolom data berformat Teks supaya angka nol di depan dan NIP panjang tidak rusak
        $s->getStyle("A2:F$last")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        // Note (komentar) di setiap kolom
        $notes = [
            'A1' => "Masukkan {$digits} digit angka NIS.\nContoh: 2324001\nTanpa spasi, huruf, atau tanda baca.",
            'B1' => "Nama lengkap siswa.\nMaksimal 100 karakter.",
            'C1' => "Pilih dari daftar (klik sel lalu panah).\nHarus sama dengan kelas di menu Kelas & Jurusan.",
            'D1' => "Pilih dari daftar (klik sel lalu panah).\nHarus sama dengan jurusan di menu Kelas & Jurusan.",
            'E1' => "NIP guru BK, angka saja.\nHarus guru BK dari kelas yang dipilih.\nLihat sheet Referensi.",
            'F1' => "NIP wali kelas, angka saja.\nLihat sheet Referensi.",
        ];

        foreach ($notes as $cell => $text) {
            $comment = $s->getComment($cell);
            $comment->setAuthor('Sistem');
            $comment->getText()->createTextRun($text);
            $comment->setWidth('260px');
            $comment->setHeight('110px');
        }

        // Dropdown kelas dan jurusan dari sheet Referensi
        $gradeRange = "Referensi!\$A\$2:\$A\${$gradeEnd}";
        $majorRange = "Referensi!\$E\$2:\$E\${$majorEnd}";

        for ($row = 2; $row <= $last; $row++) {
            $s->getCell("C$row")->setDataValidation($this->listValidation($gradeRange, 'kelas'));
            $s->getCell("D$row")->setDataValidation($this->listValidation($majorRange, 'jurusan'));
        }
    }

    private function fillReference(Worksheet $s): array
    {
        $grades = Grade::with('bk:id,name,nip')->orderBy('name')->get();
        $majors = Major::orderBy('name')->get();
        $bks = User::where('role', 'bk')->whereNotNull('nip')->orderBy('name')->get(['name', 'nip']);
        $teachers = HomeroomTeacher::orderBy('name')->get(['nip', 'name']);

        $s->fromArray(['Kelas', 'NIP Guru BK Kelas', 'Guru BK Kelas'], null, 'A1');
        $s->fromArray(['Jurusan'], null, 'E1');
        $s->fromArray(['NIP Guru BK', 'Nama Guru BK'], null, 'G1');
        $s->fromArray(['NIP Wali Kelas', 'Nama Wali Kelas'], null, 'J1');
        $s->getStyle('A1:K1')->getFont()->setBold(true);

        foreach ($grades as $i => $g) {
            $r = $i + 2;
            $this->put($s, "A$r", $g->name);
            $this->put($s, "B$r", $g->bk?->nip ?? '-');
            $this->put($s, "C$r", $g->bk?->name ?? '-');
        }
        foreach ($majors as $i => $m) {
            $this->put($s, 'E'.($i + 2), $m->name);
        }
        foreach ($bks as $i => $b) {
            $r = $i + 2;
            $this->put($s, "G$r", $b->nip);
            $this->put($s, "H$r", $b->name);
        }
        foreach ($teachers as $i => $t) {
            $r = $i + 2;
            $this->put($s, "J$r", $t->nip);
            $this->put($s, "K$r", $t->name);
        }

        foreach (range('A', 'K') as $col) {
            $s->getColumnDimension($col)->setAutoSize(true);
        }

        return [max(2, $grades->count() + 1), max(2, $majors->count() + 1)];
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
            'Kelas dan Jurusan: pilih dari dropdown. Nilainya harus sama dengan data di menu Kelas & Jurusan.',
            'NIP Guru BK: angka saja. Harus NIP guru BK dari kelas yang dipilih (lihat sheet Referensi, kolom "NIP Guru BK Kelas").',
            'NIP Wali Kelas: angka saja, harus terdaftar di menu Wali Kelas (lihat sheet Referensi).',
            'Jangan mengubah format kolom. Seluruh kolom sudah berformat Teks agar angka nol di depan dan NIP yang panjang tidak rusak.',
            'NIS yang sudah terdaftar akan diperbarui. NIS yang belum terdaftar hanya akan ditambahkan jika opsi "Tambahkan siswa baru" dicentang saat mengimpor.',
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