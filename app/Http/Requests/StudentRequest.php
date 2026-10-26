<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dibatasi middleware role:kesiswaan di route
    }

    public function rules(): array
    {
        $student = $this->route('student');
        $digits = config('school.nis_digits');

        return [
            'nis' => ['required', "digits:$digits", Rule::unique('students', 'nis')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:100'],
            'grade_id' => ['required', 'exists:grades,id'],
            'major_id' => ['required', 'exists:majors,id'],
            'homeroom_teacher_id' => ['required', 'exists:homeroom_teachers,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nis' => 'NIS',
            'name' => 'Nama siswa',
            'grade_id' => 'Kelas',
            'major_id' => 'Jurusan',
            'homeroom_teacher_id' => 'Wali kelas',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar.',
            'exists' => ':attribute tidak valid.',
            'max' => ':attribute terlalu panjang.',
            'digits' => ':attribute harus berisi :digits digit angka.',
        ];
    }
}