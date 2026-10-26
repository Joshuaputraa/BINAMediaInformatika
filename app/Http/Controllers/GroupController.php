<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Major;
use App\Models\User;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        return Inertia::render('Groups/Index', [
            'grades' => Grade::with('bk:id,name')->withCount('students')->orderBy('name')->get(),
            'majors' => Major::withCount('students')->orderBy('name')->get(),
            'bks' => User::where('role', 'bk')->orderBy('name')->get(['id', 'name', 'nip']),
        ]);
    }
}