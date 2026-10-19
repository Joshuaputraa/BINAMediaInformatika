<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Handling;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandlingService
{
    public function handle(Student $student, User $actor, array $data): Handling
    {
        if ($student->isDropout()) {
            throw ValidationException::withMessages([
                'type' => 'Siswa sudah dropout, tidak bisa ditangani lagi.',
            ]);
        }

        if (! $student->canBeHandledBy($actor)) {
            throw new AuthorizationException('Anda tidak berwenang menangani siswa ini.');
        }

        $type = StudentStatus::from($data['type']);

        if ($type === StudentStatus::WaliKelas) {
            if ($student->hasBeenHandledByWaliKelas()) {
                throw ValidationException::withMessages([
                    'type' => 'Siswa ini sudah pernah ditangani wali kelas.',
                ]);
            }

            if (! $student->homeroom_teacher_id) {
                throw ValidationException::withMessages([
                    'type' => 'Siswa ini belum memiliki wali kelas. Tentukan dulu lewat Edit siswa.',
                ]);
            }
        }

        if ($type === StudentStatus::Bk && $actor->role !== 'bk' && $student->hasBeenHandledByBk()) {
            throw ValidationException::withMessages([
                'type' => 'Siswa ini sudah pernah ditangani guru BK.',
            ]);
        }

        return DB::transaction(function () use ($student, $actor, $data, $type) {
            $teacherId = $type === StudentStatus::WaliKelas ? $student->homeroom_teacher_id : null;

            $bkId = null;
            if ($type === StudentStatus::Bk) {
                $bkId = $actor->role === 'bk' ? $actor->id : ($data['bk_user_id'] ?? null);
            }

            $handling = $student->handlings()->create([
                'homeroom_teacher_id' => $teacherId,
                'bk_user_id' => $bkId,
                'user_id' => $actor->id,
                'type' => $type,
                'notes' => $data['notes'],
            ]);

            $student->status = $type;

            if ($type === StudentStatus::Bk) {
                $student->bk_user_id = $bkId;

                if ($actor->role === 'bk') {
                    $student->bk_handled_at = now();
                }
            }

            $student->save();

            return $handling;
        });
    }
}