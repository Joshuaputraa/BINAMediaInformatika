<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Requests\StudentRequest;
use App\Models\HomeroomTeacher;
use App\Models\Student;
use App\Models\User;
use App\Services\HandlingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function __construct(private HandlingService $handlingService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isBk = $user->role === 'bk';

        $students = Student::query()
            ->with(['homeroomTeacher:id,name', 'bk:id,name', 'handlings.teacher:id,name', 'handlings.user:id,name,role'])
            ->when($isBk, fn ($q) => $q->problem())
            ->when($request->search, function ($q, $s) {
                $q->where(fn ($q) => $q->where('name', 'like', "%$s%")->orWhere('nis', 'like', "%$s%"));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($s) use ($user) {
                $s->can_handle = $s->canBeHandledBy($user);

                return $s;
            });

        return Inertia::render('Students/Index', [
            'students' => $students,
            'teachers' => $isBk ? [] : HomeroomTeacher::orderBy('name')->get(['id', 'nip', 'name']),
            'bks' => $isBk ? [] : User::where('role', 'bk')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StudentRequest $request)
    {
        Student::create($request->validated());

        return back()->with('success', 'Siswa ditambahkan.');
    }

    public function update(StudentRequest $request, Student $student)
    {
        $student->update($request->validated());

        return back()->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('success', 'Siswa dihapus.');
    }

    public function handle(Request $request, Student $student)
    {
        $isBk = $request->user()->role === 'bk';

        $data = $request->validate([
            'type' => ['required', $isBk
                ? Rule::in([StudentStatus::Bk->value])
                : Rule::enum(StudentStatus::class)->only(StudentStatus::actions())],
            'bk_user_id' => [
                'nullable',
                Rule::requiredIf(! $isBk && $request->input('type') === StudentStatus::Bk->value),
                Rule::exists('users', 'id')->where('role', 'bk'),
            ],
            'notes' => 'required|string',
        ]);

        $this->handlingService->handle($student, $request->user(), $data);

        return back()->with('success', 'Penanganan disimpan.');
    }
}