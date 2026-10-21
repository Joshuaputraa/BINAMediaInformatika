<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Handling;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BkTeacherController extends Controller
{
    use HasIndonesianValidation;

    public function index()
    {
        return Inertia::render('BkTeachers/Index', [
            'bks' => User::where('role', 'bk')->orderBy('name')->get(['id', 'nip', 'name', 'username']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nip' => ['required', 'digits_between:1,30', 'unique:users,nip'],
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
        ], $this->validationMessages(), $this->validationAttributes());

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'bk',
        ]);

        return back()->with('success', 'Guru BK ditambahkan.');
    }

    public function update(Request $request, User $bk)
    {
        abort_unless($bk->role === 'bk', 404);

        $data = $request->validate([
            'nip' => ['required', 'digits_between:1,30', Rule::unique('users', 'nip')->ignore($bk->id)],
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($bk->id)],
            'password' => ['nullable', 'string', 'min:6'],
        ], $this->validationMessages(), $this->validationAttributes());

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $bk->update($data);

        return back()->with('success', 'Guru BK diperbarui.');
    }

    public function destroy(User $bk)
    {
        abort_unless($bk->role === 'bk', 404);

        if (Handling::where('user_id', $bk->id)->exists()) {
            return back()->withErrors([
                'delete' => 'Guru BK ini sudah memiliki riwayat penanganan sehingga tidak bisa dihapus.',
            ]);
        }

        $bk->delete();

        return back()->with('success', 'Guru BK dihapus.');
    }
}