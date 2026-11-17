<?php

namespace App\Http\Controllers;

use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotion) {}

    /** Siswa hasil filter (maksimal 3000), untuk "Pilih semua hasil filter". */
    public function ids(Request $request)
    {
        $students = $this->promotion->query($this->promotion->filters($request))
            ->with(['classroom.grade:id,name', 'classroom.major:id,name'])
            ->orderBy('name')
            ->limit(3000)
            ->get(['id', 'nis', 'name', 'classroom_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'nis' => $s->nis,
                'name' => $s->name,
                'classroom' => $s->classroom?->name,
            ]);

        return response()->json(['students' => $students]);
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:3000'],
            'student_ids.*' => ['integer'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:homeroom_teachers,id'],
            'graduate_existing' => ['nullable', 'boolean'],
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'student_ids.max' => 'Maksimal 3000 siswa sekali proses.',
            'exists' => ':attribute tidak valid.',
        ], [
            'classroom_id' => 'Kelas tujuan',
            'homeroom_teacher_id' => 'Wali kelas',
        ]);

        if (empty($data['classroom_id']) && empty($data['homeroom_teacher_id'])) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Pilih kelas tujuan atau wali kelas baru.',
            ]);
        }

        $result = $this->promotion->move(
            $data['student_ids'],
            $data['classroom_id'] ?? null,
            $data['homeroom_teacher_id'] ?? null,
            $request->boolean('graduate_existing'),
        );

        $message = "{$result['moved']} siswa berhasil diperbarui";

        if ($result['graduated'] > 0) {
            $message .= ", {$result['graduated']} siswa lama diluluskan";
        }

        return back()->with('success', $message.'.');
    }

    public function graduate(Request $request)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:3000'],
            'student_ids.*' => ['integer'],
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'student_ids.max' => 'Maksimal 3000 siswa sekali proses.',
        ]);

        $count = $this->promotion->graduate($data['student_ids']);

        return back()->with('success', "$count siswa diluluskan.");
    }
}