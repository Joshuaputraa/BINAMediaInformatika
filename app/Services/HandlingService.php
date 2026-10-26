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
            throw $this->fail('Siswa sudah dropout, tidak bisa ditangani lagi.');
        }

        if (! $student->canBeHandledBy($actor)) {
            throw new AuthorizationException('Anda tidak berwenang menangani siswa ini.');
        }

        $type = StudentStatus::from($data['type']);

        if ($type === StudentStatus::WaliKelas) {
            if ($student->hasBeenHandledByWaliKelas()) {
                throw $this->fail('Siswa ini sudah pernah ditangani wali kelas.');
            }
            if (! $student->homeroom_teacher_id) {
                throw $this->fail('Siswa ini belum memiliki wali kelas. Tentukan dulu lewat Edit siswa.');
            }
        }

        if ($type === StudentStatus::Bk && $actor->role !== 'bk') {
            if (! $student->bk_user_id) {
                throw $this->fail('Siswa ini belum memiliki guru BK. Tentukan kelasnya lewat Edit siswa.');
            }
            if ($student->hasBeenHandledByBk()) {
                throw $this->fail('Siswa ini sudah pernah ditangani guru BK.');
            }
        }

        if (in_array($type, [StudentStatus::Sp1, StudentStatus::Sp2, StudentStatus::Sp3], true)) {
            $level = (int) substr($type->value, 2);

            if ($level <= $student->spLevel()) {
                throw $this->fail("SP $level sudah diberikan atau terlewati.");
            }
        }

        return DB::transaction(function () use ($student, $actor, $data, $type) {
            $handling = $student->handlings()->create([
                'homeroom_teacher_id' => $type === StudentStatus::WaliKelas ? $student->homeroom_teacher_id : null,
                'bk_user_id' => $type === StudentStatus::Bk ? $student->bk_user_id : null,
                'user_id' => $actor->id,
                'type' => $type,
                'notes' => $data['notes'],
            ]);

            $student->status = $type;

            if ($type === StudentStatus::Bk && $actor->role === 'bk') {
                $student->bk_handled_at = now();
            }

            $student->save();

            return $handling;
        });
    }

    private function fail(string $message): ValidationException
    {
        return ValidationException::withMessages(['type' => $message]);
    }
}