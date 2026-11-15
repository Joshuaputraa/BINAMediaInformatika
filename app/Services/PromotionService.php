<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    public const FILTER_KEYS = ['search', 'classroom_id', 'major_id', 'homeroom_teacher_id'];

    /** Ambil filter dari request, dibersihkan menjadi teks biasa. */
    public function filters(Request $request): array
    {
        return collect(self::FILTER_KEYS)
            ->mapWithKeys(function ($key) use ($request) {
                $value = $request->input($key);

                return [$key => is_scalar($value) ? trim((string) $value) : ''];
            })
            ->all();
    }

    /** Siswa yang bisa dipindahkan (siswa dropout tidak ikut), sesuai filter. */
    public function query(array $f): Builder
    {
        return Student::query()
            ->where('status', '!=', StudentStatus::Dropout->value)
            ->when($f['search'] ?? '', function ($q, $s) {
                $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('nis', 'like', "%$s%"));
            })
            ->when($f['classroom_id'] ?? '', function ($q, $v) {
                $v === 'none' ? $q->whereNull('classroom_id') : $q->where('classroom_id', (int) $v);
            })
            ->when($f['major_id'] ?? '', function ($q, $v) {
                $q->whereHas('classroom', fn ($c) => $c->where('major_id', (int) $v));
            })
            ->when($f['homeroom_teacher_id'] ?? '', fn ($q, $v) => $q->where('homeroom_teacher_id', (int) $v));
    }

    /**
     * Pindahkan siswa ke kelas lain dan/atau ganti wali kelas.
     * Guru BK otomatis mengikuti tingkat kelas tujuan. Mengembalikan jumlah siswa yang diproses.
     */
    public function move(array $ids, ?int $classroomId, ?int $teacherId): int
    {
        return DB::transaction(function () use ($ids, $classroomId, $teacherId) {
            $base = Student::whereIn('id', $ids)->where('status', '!=', StudentStatus::Dropout->value);
            $count = (clone $base)->count();

            $changes = [];

            if ($classroomId) {
                $classroom = Classroom::with('grade')->findOrFail($classroomId);

                // siswa yang benar-benar pindah kelas: pengawasan wali kelas/guru BK kelas lama berakhir
                (clone $base)
                    ->whereNotNull('classroom_id')
                    ->where('classroom_id', '!=', $classroom->id)
                    ->whereIn('status', [StudentStatus::WaliKelas->value, StudentStatus::Bk->value])
                    ->update(['status' => StudentStatus::Normal->value]);

                $changes['classroom_id'] = $classroom->id;
                $changes['bk_user_id'] = $classroom->grade->bk_user_id; // guru BK mengikuti tingkat kelas tujuan
            }

            if ($teacherId) {
                $changes['homeroom_teacher_id'] = $teacherId;
            }

            $base->update($changes);

            return $count;
        });
    }
}