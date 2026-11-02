<?php

namespace App\Services;

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
        $student->update($this->withBk($data));

        return $student;
    }

    /** Guru BK siswa mengikuti guru BK tingkat dari kelasnya. */
    private function withBk(array $data): array
    {
        $data['bk_user_id'] = Classroom::with('grade')->findOrFail($data['classroom_id'])->grade->bk_user_id;

        return $data;
    }
}