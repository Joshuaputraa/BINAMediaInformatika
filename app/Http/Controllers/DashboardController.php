<?php

namespace App\Http\Controllers;

use App\Models\HomeroomTeacher;
use App\Models\Student;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $relations = ['homeroomTeacher:id,name', 'bk:id,name', 'handlings.teacher:id,name', 'handlings.user:id,name,role'];

        // map() dipakai (bukan each) karena each() berhenti kalau callback mengembalikan false
        $mark = function ($s) use ($user) {
            $s->can_handle = $s->canBeHandledBy($user);

            return $s;
        };

        $data = [
            'total' => Student::count(),
            'problem' => Student::problem()->count(),
            'bkCount' => User::where('role', 'bk')->count(),
            'byStatus' => Student::problem()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'monthly' => $this->reports->monthlyProblemStudents(),
            'problemStudents' => [],
            'teachers' => [],
            'bks' => [],
            'referrals' => [],
            'handled' => [],
        ];

        if ($user->role === 'bk') {
            $mine = Student::with($relations)
                ->where('bk_user_id', $user->id)
                ->orderBy('name')
                ->get()
                ->map($mark);

            $data['referrals'] = $mine->filter(fn ($s) => $s->can_handle)->values();
            $data['handled'] = $mine->reject(fn ($s) => $s->can_handle)->values();
        } else {
            $data['problemStudents'] = Student::with($relations)
                ->problem()
                ->orderBy('name')
                ->get()
                ->map($mark);

            $data['teachers'] = HomeroomTeacher::orderBy('name')->get(['id', 'nip', 'name']);
            $data['bks'] = User::where('role', 'bk')->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('Dashboard', $data);
    }
}