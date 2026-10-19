<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasIndonesianValidation;
use App\Models\Handling;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class KesiswaanUserController extends Controller
{
    use HasIndonesianValidation;

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
        ], $this->validationMessages(), $this->validationAttributes());

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'kesiswaan',
        ]);

        return back()->with('success', 'Akun kesiswaan ditambahkan.');
    }

    public function update(Request $request, User $kesiswaan)
    {
        abort_unless($kesiswaan->role === 'kesiswaan', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($kesiswaan->id)],
            'password' => ['nullable', 'string', 'min:6'],
        ], $this->validationMessages(), $this->validationAttributes());

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $kesiswaan->update($data);

        return back()->with('success', 'Akun kesiswaan diperbarui.');
    }

    public function destroy(Request $request, User $kesiswaan)
    {
        abort_unless($kesiswaan->role === 'kesiswaan', 404);

        if ((int) $kesiswaan->id === (int) $request->user()->id) {
            return back()->withErrors(['delete' => 'Anda tidak bisa menghapus akun yang sedang dipakai.']);
        }

        if (Handling::where('user_id', $kesiswaan->id)->exists()) {
            return back()->withErrors([
                'delete' => 'Akun ini sudah memiliki riwayat penanganan sehingga tidak bisa dihapus.',
            ]);
        }

        $kesiswaan->delete();

        return back()->with('success', 'Akun kesiswaan dihapus.');
    }
}