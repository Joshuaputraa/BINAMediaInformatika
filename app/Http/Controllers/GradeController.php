<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    use HasIndonesianValidation;

    public function store(Request $request)
    {
        Grade::create($this->validated($request));

        return back()->with('success', 'Kelas ditambahkan.');
    }

    public function update(Request $request, Grade $grade)
    {
        $data = $this->validated($request, $grade);

        DB::transaction(function () use ($grade, $data) {
            $grade->update($data);

            // siswa kelas ini ikut berpindah ke guru BK yang baru
            $grade->students()->update(['bk_user_id' => $data['bk_user_id']]);
        });

        return back()->with('success', 'Kelas diperbarui.');
    }

    public function destroy(Grade $grade)
    {
        $count = $grade->students()->count();

        if ($count > 0) {
            return back()->withErrors([
                'delete' => "Kelas ini masih memiliki $count siswa. Pindahkan siswanya terlebih dahulu.",
            ]);
        }

        $grade->delete();

        return back()->with('success', 'Kelas dihapus.');
    }

    /** Daftar siswa kelas ini (JSON, dipanggil saat baris kelas diklik). */
    public function students(Grade $grade)
    {
        $students = $grade->students()
            ->with('major:id,name')
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'status', 'major_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'status' => $s->status->value,
                'extra' => $s->major?->name,
            ]);

        return response()->json(['title' => "Kelas {$grade->name}", 'extraLabel' => 'Jurusan', 'students' => $students]);
    }

    private function validated(Request $request, ?Grade $grade = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('grades', 'name')->ignore($grade?->id)],
            'bk_user_id' => ['required', Rule::exists('users', 'id')->where('role', 'bk')],
        ], $this->validationMessages(), $this->validationAttributes());
    }
}