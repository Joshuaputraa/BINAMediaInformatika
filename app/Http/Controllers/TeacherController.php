<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\HomeroomTeacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeacherController extends Controller
{
    use HasIndonesianValidation;

    public function index()
    {
        return Inertia::render('Teachers/Index', [
            'teachers' => HomeroomTeacher::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nip' => ['required', 'digits_between:1,30', 'unique:homeroom_teachers,nip'],
            'name' => ['required', 'string', 'max:100'],
        ], $this->validationMessages(), $this->validationAttributes());

        HomeroomTeacher::create($data);

        return back()->with('success', 'Wali kelas ditambahkan.');
    }

    public function update(Request $request, HomeroomTeacher $teacher)
    {
        $data = $request->validate([
            'nip' => ['required', 'digits_between:1,30', Rule::unique('homeroom_teachers', 'nip')->ignore($teacher->id)],
            'name' => ['required', 'string', 'max:100'],
        ], $this->validationMessages(), $this->validationAttributes());

        $teacher->update($data);

        return back()->with('success', 'Wali kelas diperbarui.');
    }

    public function destroy(HomeroomTeacher $teacher)
    {
        $teacher->delete();

        return back()->with('success', 'Wali kelas dihapus.');
    }
}