<?php

namespace Database\Seeders;

use App\Models\HomeroomTeacher;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Guru BK', 'username' => 'gurubk', 'password' => Hash::make('password'), 'role' => 'bk']);
        User::create(['name' => 'Kesiswaan', 'username' => 'kesiswaan', 'password' => Hash::make('password'), 'role' => 'kesiswaan']);

        HomeroomTeacher::create(['nip' => '198501012010011001', 'name' => 'Budi Santoso']);
        HomeroomTeacher::create(['nip' => '199002022015022002', 'name' => 'Siti Aminah']);

        Student::create(['nis' => '2324001', 'name' => 'Andi Pratama']);
        Student::create(['nis' => '2324002', 'name' => 'Rina Marlina']);
        Student::create(['nis' => '2324003', 'name' => 'Dimas Saputra']);
    }
}