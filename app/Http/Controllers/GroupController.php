<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Major;
use App\Models\User;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Groups/Index', [
            'classrooms' => Classroom::with(['grade.bk:id,name', 'major:id,name'])
                ->withCount('students')
                ->get()
                ->sortBy(fn ($c) => $c->name, SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'grades' => Grade::with('bk:id,name')->withCount(['classrooms', 'students'])->orderBy('name')->get(),
            'majors' => Major::withCount(['classrooms', 'students'])->orderBy('name')->get(),
            'bks' => User::where('role', 'bk')->orderBy('name')->get(['id', 'name', 'nip']),
        ]);
    }
}