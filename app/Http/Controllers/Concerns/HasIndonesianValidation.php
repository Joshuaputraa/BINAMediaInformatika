<?php

namespace App\Http\Controllers\Concerns;

trait HasIndonesianValidation
{
    protected function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah dipakai.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute terlalu panjang.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'digits_between' => ':attribute hanya boleh berisi angka (maksimal :max digit).',
            'current_password' => 'Password saat ini salah.',
            'exists' => ':attribute tidak valid.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nip' => 'NIP',
            'name' => 'Nama',
            'username' => 'Username',
            'password' => 'Password',
            'current_password' => 'Password saat ini',
            'bk_user_id' => 'Guru BK',
        ];
    }
}