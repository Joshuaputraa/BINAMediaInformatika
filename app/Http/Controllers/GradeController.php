<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    use HasIndonesianValidation;

    public function store(Request $request)
    {
        Grade::create($this->validated($request));

        return back()->with('success', 'Tingkat ditambahkan.');
    }

    public function update(Request $request, Grade $grade)
    {
        $data = $this->validated($request, $grade);

        DB::transaction(function () use ($grade, $data) {
            $grade->update($data);

            // siswa di semua kelas tingkat ini ikut berpindah ke guru BK yang baru
            Student::whereIn('classroom_id', $grade->classrooms()->pluck('id'))
                ->update(['bk_user_id' => $data['bk_user_id']]);
        });

        return back()->with('success', 'Tingkat diperbarui.');
    }

    public function destroy(Grade $grade)
    {
        $count = $grade->classrooms()->count();

        if ($count > 0) {
            return back()->withErrors([
                'delete' => "Tingkat ini masih memiliki $count kelas. Hapus atau pindahkan kelasnya terlebih dahulu.",
            ]);
        }

        $grade->delete();

        return back()->with('success', 'Tingkat dihapus.');
    }

    /** Daftar siswa satu tingkat (JSON, dipanggil saat baris tingkat diklik). */
    public function students(Grade $grade)
    {
        $students = Student::whereHas('classroom', fn ($q) => $q->where('grade_id', $grade->id))
            ->with(['classroom.grade:id,name', 'classroom.major:id,name'])
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'status', 'classroom_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'status' => $s->status->value,
                'extra' => $s->classroom?->name,
            ]);

        return response()->json(['title' => "Tingkat {$grade->name}", 'extraLabel' => 'Kelas', 'students' => $students]);
    }

    private function validated(Request $request, ?Grade $grade = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('grades', 'name')->ignore($grade?->id)],
            'bk_user_id' => ['required', Rule::exists('users', 'id')->where('role', 'bk')],
        ], $this->validationMessages(), $this->validationAttributes());
    }
}