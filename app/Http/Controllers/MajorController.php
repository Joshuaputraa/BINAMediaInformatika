<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MajorController extends Controller
{
    use HasIndonesianValidation;

    public function store(Request $request)
    {
        Major::create($this->validated($request));

        return back()->with('success', 'Jurusan ditambahkan.');
    }

    public function update(Request $request, Major $major)
    {
        $major->update($this->validated($request, $major));

        return back()->with('success', 'Jurusan diperbarui.');
    }

    public function destroy(Major $major)
    {
        $count = $major->students()->count();

        if ($count > 0) {
            return back()->withErrors([
                'delete' => "Jurusan ini masih memiliki $count siswa. Pindahkan siswanya terlebih dahulu.",
            ]);
        }

        $major->delete();

        return back()->with('success', 'Jurusan dihapus.');
    }

    /** Daftar siswa jurusan ini (JSON, dipanggil saat baris jurusan diklik). */
    public function students(Major $major)
    {
        $students = $major->students()
            ->with('grade:id,name')
            ->orderBy('name')
            ->get(['id', 'nis', 'name', 'status', 'grade_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'status' => $s->status->value,
                'extra' => $s->grade?->name,
            ]);

        return response()->json(['title' => "Jurusan {$major->name}", 'extraLabel' => 'Kelas', 'students' => $students]);
    }

    private function validated(Request $request, ?Major $major = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('majors', 'name')->ignore($major?->id)],
        ], $this->validationMessages(), $this->validationAttributes());
    }
}