<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    use HasIndonesianValidation;

    public function store(Request $request)
    {
        Classroom::create($this->validated($request));

        return back()->with('success', 'Kelas ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom)
    {
        $data = $this->validated($request, $classroom);

        DB::transaction(function () use ($classroom, $data) {
            $classroom->update($data);

            // guru BK siswa mengikuti tingkat kelas (bisa berubah kalau tingkatnya diganti)
            $classroom->students()->update([
                'bk_user_id' => Grade::findOrFail($data['grade_id'])->bk_user_id,
            ]);
        });

        return back()->with('success', 'Kelas diperbarui.');
    }

    public function destroy(Classroom $classroom)
    {
        $count = $classroom->students()->count();

        if ($count > 0) {
            return back()->withErrors([
                'delete' => "Kelas ini masih memiliki $count siswa. Pindahkan siswanya terlebih dahulu.",
            ]);
        }

        $classroom->delete();

        return back()->with('success', 'Kelas dihapus.');
    }

    /** Daftar siswa kelas ini (JSON, dipanggil saat baris kelas diklik). */
    public function students(Classroom $classroom)
    {
        $students = $classroom->students()
            ->with('homeroomTeacher:id,name')
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'status', 'homeroom_teacher_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'status' => $s->status->value,
                'extra' => $s->homeroomTeacher?->name,
            ]);

        return response()->json([
            'title' => "Kelas {$classroom->name}",
            'extraLabel' => 'Wali kelas',
            'students' => $students,
        ]);
    }

    private function validated(Request $request, ?Classroom $classroom = null): array
    {
        return $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'major_id' => ['required', 'exists:majors,id'],
            'number' => [
                'required', 'integer', 'between:1,99',
                Rule::unique('classrooms', 'number')
                    ->where(fn ($q) => $q
                        ->where('grade_id', $request->input('grade_id'))
                        ->where('major_id', $request->input('major_id')))
                    ->ignore($classroom?->id),
            ],
        ], array_merge($this->validationMessages(), [
            'number.unique' => 'Kelas dengan tingkat, jurusan, dan nomor ini sudah ada.',
        ]), $this->validationAttributes());
    }
}