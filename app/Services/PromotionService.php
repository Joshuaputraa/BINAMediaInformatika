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

    /** Siswa dengan status ini tidak ikut dipindahkan atau diluluskan. */
    private function inactive(): array
    {
        return [StudentStatus::Dropout->value, StudentStatus::Graduated->value];
    }

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

    /** Siswa aktif yang bisa dipindahkan, sesuai filter. */
    public function query(array $f): Builder
    {
        return Student::query()
            ->whereNotIn('status', $this->inactive())
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
     * Guru BK otomatis mengikuti tingkat kelas tujuan.
     * Jika kelas tujuan berada di tingkat akhir dan $graduateExisting aktif,
     * siswa lama di kelas itu (yang tidak ikut dipilih) diluluskan lebih dulu.
     *
     * @return array{moved:int, graduated:int}
     */
    public function move(array $ids, ?int $classroomId, ?int $teacherId, bool $graduateExisting = false): array
    {
        return DB::transaction(function () use ($ids, $classroomId, $teacherId, $graduateExisting) {
            $base = Student::whereIn('id', $ids)->whereNotIn('status', $this->inactive());
            $moved = (clone $base)->count();
            $graduated = 0;
            $changes = [];

            if ($classroomId) {
                $classroom = Classroom::with('grade')->findOrFail($classroomId);

                if ($graduateExisting && $classroom->grade->is_final) {
                    $graduated = $this->graduateQuery(
                        Student::where('classroom_id', $classroom->id)->whereNotIn('id', $ids)
                    );
                }

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

            return ['moved' => $moved, 'graduated' => $graduated];
        });
    }

    /** Luluskan siswa terpilih. */
    public function graduate(array $ids): int
    {
        return DB::transaction(fn () => $this->graduateQuery(Student::whereIn('id', $ids)));
    }

    /** Siswa lulus: status Lulus, keluar dari kelas. Riwayat penanganan tetap tersimpan. */
    private function graduateQuery(Builder $query): int
    {
        $query->whereNotIn('status', $this->inactive());

        $count = (clone $query)->count();

        $query->update([
            'status' => StudentStatus::Graduated->value,
            'graduated_at' => now(),
            'classroom_id' => null,
        ]);

        return $count;
    }
}