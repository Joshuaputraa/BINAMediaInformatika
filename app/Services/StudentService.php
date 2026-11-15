<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Student;

class StudentService
{
    public function create(array $data): Student
    {
        return Student::create($this->withBk($data));
    }

    public function update(Student $student, array $data): Student
    {
        $moved = $student->classroom_id !== null
            && (int) $student->classroom_id !== (int) $data['classroom_id'];

        $student->fill($this->withBk($data));

        // pindah kelas: pengawasan wali kelas/guru BK kelas lama berakhir
        if ($moved && in_array($student->status, [StudentStatus::WaliKelas, StudentStatus::Bk], true)) {
            $student->status = StudentStatus::Normal;
        }

        $student->save();

        return $student;
    }

    /** Guru BK siswa selalu mengikuti guru BK tingkat dari kelasnya. */
    private function withBk(array $data): array
    {
        $data['bk_user_id'] = Classroom::with('grade')->findOrFail($data['classroom_id'])->grade->bk_user_id;

        return $data;
    }
}