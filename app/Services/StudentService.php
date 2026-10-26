<?php

namespace App\Services;

use App\Models\Grade;
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

    /** Guru BK siswa selalu mengikuti guru BK kelasnya. */
    private function withBk(array $data): array
    {
        $data['bk_user_id'] = Grade::findOrFail($data['grade_id'])->bk_user_id;

        return $data;
    }
}