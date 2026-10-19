<?php

namespace App\Services;

use App\Models\Handling;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /** Jumlah siswa unik yang tercatat penanganannya tiap bulan. */
    public function monthlyProblemStudents(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $counts = Handling::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(DISTINCT student_id) as total")
            ->where('created_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return collect(range(0, $months - 1))
            ->map(function ($i) use ($start, $counts) {
                $month = $start->copy()->addMonths($i)->format('Y-m');

                return ['month' => $month, 'total' => (int) $counts->get($month, 0)];
            })
            ->all();
    }

    /** Daftar penanganan sesuai filter, terbaru di atas. $bkId membatasi ke siswa yang dirujuk ke guru BK tertentu. */
    public function rows(?string $from, ?string $to, ?string $type, ?int $bkId = null): Builder
    {
        return $this->base($from, $to, $type, $bkId)
            ->with([
                'student:id,nis,name,homeroom_teacher_id,bk_user_id',
                'student.homeroomTeacher:id,name',
                'student.bk:id,name',
                'teacher:id,name',
                'bk:id,name',
                'user:id,name',
            ])
            ->latest();
    }

    public function summary(?string $from, ?string $to, ?string $type, ?int $bkId = null): array
    {
        $base = $this->base($from, $to, $type, $bkId);

        $byType = (clone $base)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return [
            'students' => (clone $base)->distinct()->count('student_id'),
            'total' => (int) $byType->sum(),
            'byType' => $byType,
        ];
    }

    private function base(?string $from, ?string $to, ?string $type, ?int $bkId): Builder
    {
        return Handling::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($bkId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('bk_user_id', $bkId)));
    }
}