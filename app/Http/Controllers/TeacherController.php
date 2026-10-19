<?php

namespace App\Http\Controllers;

use App\Models\HomeroomTeacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index()
    {
        return Inertia::render('Teachers/Index', [
            'teachers' => HomeroomTeacher::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nip' => 'required|unique:homeroom_teachers,nip',
            'name' => 'required|string|max:100',
        ]);

        HomeroomTeacher::create($data);

        return back()->with('success', 'Wali kelas ditambahkan.');
    }

    public function update(Request $request, HomeroomTeacher $teacher)
    {
        $data = $request->validate([
            'nip' => ['required', Rule::unique('homeroom_teachers', 'nip')->ignore($teacher->id)],
            'name' => 'required|string|max:100',
        ]);

        $teacher->update($data);

        return back()->with('success', 'Wali kelas diperbarui.');
    }

    public function destroy(HomeroomTeacher $teacher)
    {
        $teacher->delete();

        return back()->with('success', 'Wali kelas dihapus.');
    }
}