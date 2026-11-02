<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\HomeroomTeacher;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Throwable;

class StudentImportService
{
    private const MAX_ERRORS = 300;

    private array $errors = [];

    private int $errorCount = 0;

    public function import(UploadedFile $file, bool $allowCreate, bool $dryRun): ImportResult
    {
        $this->errors = [];
        $this->errorCount = 0;

        $rows = $this->readRows($file);

        if ($rows !== null) {
            $plan = $this->validate($rows, $allowCreate);

            if ($this->errorCount === 0) {
                return $this->finish($plan, $dryRun);
            }
        }

        return new ImportResult($this->errors, $this->errorCount, 0, 0, $dryRun);
    }

    /** Baca sheet Template menjadi daftar baris. Mengembalikan null jika file tidak sesuai. */
    private function readRows(UploadedFile $file): ?array
    {
        try {
            $reader = new XlsxReader();
            $reader->setReadDataOnly(true);   // abaikan seluruh format/gaya sel
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (Throwable) {
            $this->error(0, 'File', 'File tidak bisa dibaca. Gunakan file .xlsx dari template yang disediakan.');

            return null;
        }

        $sheet = $spreadsheet->getSheetByName('Template') ?? $spreadsheet->getSheet(0);

        if ($sheet->getHighestDataRow() - 1 > StudentTemplateService::MAX_ROWS) {
            $this->error(0, 'File', 'Maksimal '.StudentTemplateService::MAX_ROWS.' baris data per file.');

            return null;
        }

        // Nilai mentah: rumus tidak dihitung dan format tampilan tidak dipakai
        $raw = $sheet->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();

        $expected = StudentTemplateService::HEADERS;
        $header = array_map(
            fn ($v) => mb_strtolower($this->clean($v)),
            array_pad(array_slice($raw[0] ?? [], 0, count($expected)), count($expected), null)
        );

        if ($header !== array_map('mb_strtolower', $expected)) {
            $this->error(0, 'Judul kolom', 'Judul kolom tidak sesuai template. Urutan yang benar: '.implode(', ', $expected).'. Unduh template terbaru.');

            return null;
        }

        $rows = [];

        foreach (array_slice($raw, 1, null, true) as $i => $cells) {
            $cells = array_pad(array_slice($cells, 0, count($expected)), count($expected), null);

            // Baris dianggap kosong bila kolom isian kosong (kolom otomatis berisi rumus)
            $filled = collect(StudentTemplateService::INPUT_COLUMNS)->contains(fn ($c) => $this->clean($cells[$c]) !== '');

            if ($filled) {
                $rows[] = ['row' => $i + 1, 'cells' => $cells];
            }
        }

        if (! $rows) {
            $this->error(0, 'File', 'File tidak berisi data siswa.');

            return null;
        }

        return $rows;
    }

    /** Validasi semua baris. Tidak ada yang disimpan di tahap ini. */
    private function validate(array $rows, bool $allowCreate): array
    {
        $digits = (int) config('school.nis_digits');

        $classrooms = Classroom::with(['grade:id,name,bk_user_id', 'major:id,name'])->get()
            ->keyBy(fn ($c) => $this->key($c->name));
        $teachers = HomeroomTeacher::all()->keyBy('nip');

        $nisList = collect($rows)->map(fn ($r) => $this->clean($r['cells'][0]))->filter(fn ($v) => ctype_digit($v))->all();
        $existing = Student::whereIn('nis', $nisList)->get()->keyBy('nis');

        $seen = [];
        $plan = [];

        foreach ($rows as ['row' => $n, 'cells' => $cells]) {
            $before = $this->errorCount;

            $nis = $this->clean($cells[0]);
            $name = $this->clean($cells[1]);
            $className = $this->clean($cells[2]);
            $teacherNip = $this->clean($cells[5]);

            // Kolom isian tidak boleh berisi rumus
            foreach ([[$nis, 'NIS'], [$name, 'Nama Siswa'], [$className, 'Kelas'], [$teacherNip, 'NIP Wali Kelas']] as [$value, $column]) {
                if (str_starts_with($value, '=')) {
                    $this->error($n, $column, 'Berisi rumus Excel. Isi dengan nilai biasa (tempel sebagai Values).');
                }
            }

            // NIS
            $nisOk = false;
            if ($nis === '') {
                $this->error($n, 'NIS', 'Wajib diisi.');
            } elseif (str_starts_with($nis, '=')) {
                // sudah dilaporkan di atas
            } elseif (! ctype_digit($nis)) {
                $this->error($n, 'NIS', "Hanya boleh berisi angka (ditemukan \"$nis\").");
            } elseif (strlen($nis) !== $digits) {
                $this->error($n, 'NIS', "Harus $digits digit angka (ditemukan ".strlen($nis).' digit). Jika NIS berawalan 0, pastikan kolom berformat Teks.');
            } elseif (isset($seen[$nis])) {
                $this->error($n, 'NIS', "NIS ganda, sudah ada di baris {$seen[$nis]}.");
            } else {
                $seen[$nis] = $n;
                $nisOk = true;
            }

            // Nama
            if ($name === '') {
                $this->error($n, 'Nama Siswa', 'Wajib diisi.');
            } elseif (mb_strlen($name) > 100) {
                $this->error($n, 'Nama Siswa', 'Maksimal 100 karakter.');
            }

            // Kelas (jurusan dan guru BK mengikuti kelas)
            $classroom = null;
            if ($className === '') {
                $this->error($n, 'Kelas', 'Wajib diisi.');
            } elseif (! str_starts_with($className, '=') && ! ($classroom = $classrooms[$this->key($className)] ?? null)) {
                $this->error($n, 'Kelas', "Kelas \"$className\" tidak ditemukan. Pilih dari dropdown atau tambahkan kelasnya di menu Kelas & Jurusan.");
            }

            // Wali kelas
            $teacher = null;
            if ($teacherNip === '') {
                $this->error($n, 'NIP Wali Kelas', 'Wajib diisi.');
            } elseif (str_starts_with($teacherNip, '=')) {
                // sudah dilaporkan di atas
            } elseif ($this->lostPrecision($cells[5])) {
                $this->error($n, 'NIP Wali Kelas', 'Terbaca sebagai angka sehingga digitnya terpotong. Ketik ulang pada kolom berformat Teks.');
            } elseif (! ctype_digit($teacherNip)) {
                $this->error($n, 'NIP Wali Kelas', 'Hanya boleh berisi angka.');
            } elseif (! ($teacher = $teachers[$teacherNip] ?? null)) {
                $this->error($n, 'NIP Wali Kelas', "NIP $teacherNip tidak terdaftar sebagai wali kelas.");
            }

            // NIS sudah terdaftar?
            $student = $nisOk ? ($existing[$nis] ?? null) : null;
            if ($nisOk && ! $student && ! $allowCreate) {
                $this->error($n, 'NIS', 'NIS belum terdaftar. Centang "Tambahkan siswa baru" jika ini siswa baru.');
            }

            if ($this->errorCount === $before) {
                $plan[] = [
                    'nis' => $nis,
                    'student' => $student,
                    'attrs' => [
                        'name' => $name,
                        'classroom_id' => $classroom->id,
                        'homeroom_teacher_id' => $teacher->id,
                        'bk_user_id' => $classroom->grade->bk_user_id,
                    ],
                ];
            }
        }

        return $plan;
    }

    /** Semua baris valid: simpan (atau hanya hitung bila dry run). Semua-atau-tidak-sama-sekali. */
    private function finish(array $plan, bool $dryRun): ImportResult
    {
        $updated = collect($plan)->filter(fn ($p) => $p['student'])->count();
        $created = count($plan) - $updated;

        if ($dryRun) {
            return new ImportResult([], 0, $updated, $created, true);
        }

        try {
            DB::transaction(function () use ($plan) {
                foreach ($plan as $p) {
                    if ($p['student']) {
                        $p['student']->update($p['attrs']);
                    } else {
                        Student::create(['nis' => $p['nis']] + $p['attrs']);
                    }
                }
            });
        } catch (Throwable $e) {
            report($e);
            $this->error(0, 'Sistem', 'Terjadi kesalahan saat menyimpan. Tidak ada data yang diubah.');

            return new ImportResult($this->errors, $this->errorCount, 0, 0, false);
        }

        return new ImportResult([], 0, $updated, $created, false);
    }

    /** Nama kelas dibandingkan tanpa membedakan huruf besar-kecil dan spasi: "x rpl1" = "X RPL 1". */
    private function key(string $name): string
    {
        return mb_strtolower(str_replace(' ', '', $name));
    }

    /** Buang format dan karakter tak terlihat bawaan Excel. */
    private function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            // 2324001.0 atau 2.324001E+6 menjadi "2324001"
            $value = (is_float($value) && floor($value) == $value && abs($value) < 1e15)
                ? number_format($value, 0, '', '')
                : (string) $value;
        }

        $value = (string) $value;
        $value = preg_replace('/[\x{00A0}\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', ' ', $value); // spasi keras, zero-width, BOM
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);              // karakter kontrol
        $value = preg_replace('/\s+/u', ' ', $value);                                          // spasi ganda, tab, baris baru
        $value = preg_replace('/^[\'"`]+|[\'"`]+$/u', '', trim($value));                       // tanda kutip yang terbawa

        return trim($value);
    }

    /** Angka dengan 15 digit atau lebih sudah kehilangan presisi di Excel. */
    private function lostPrecision(mixed $raw): bool
    {
        return (is_float($raw) || is_int($raw)) && abs((float) $raw) >= 1e15;
    }

    private function error(int $row, string $column, string $message): void
    {
        $this->errorCount++;

        if (count($this->errors) < self::MAX_ERRORS) {
            $this->errors[] = ['row' => $row, 'column' => $column, 'message' => $message];
        }
    }
}