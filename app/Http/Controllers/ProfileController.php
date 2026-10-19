<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    use HasIndonesianValidation;

    public function edit(Request $request)
    {
        return Inertia::render('Settings/Index', [
            'me' => $request->user()->only('id', 'name', 'username'),
            'accounts' => User::where('role', 'kesiswaan')->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
        ], $this->validationMessages(), $this->validationAttributes());

        $user->update($data);

        return back()->with('success', 'Profil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->validationMessages(), $this->validationAttributes());

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password diperbarui.');
    }
}