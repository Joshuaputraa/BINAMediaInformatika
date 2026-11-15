<?php

namespace App\Http\Controllers;

use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotion) {}

    /** Semua ID siswa hasil filter, untuk "Pilih semua hasil filter". */
    public function ids(Request $request)
    {
        $ids = $this->promotion->query($this->promotion->filters($request))
            ->orderBy('name')
            ->limit(3000)
            ->pluck('id');

        return response()->json(['ids' => $ids]);
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1', 'max:3000'],
            'student_ids.*' => ['integer'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:homeroom_teachers,id'],
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

        $count = $this->promotion->move(
            $data['student_ids'],
            $data['classroom_id'] ?? null,
            $data['homeroom_teacher_id'] ?? null,
        );

        return back()->with('success', "$count siswa berhasil diperbarui.");
    }
}