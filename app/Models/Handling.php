<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Handling extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'homeroom_teacher_id', 'bk_user_id', 'user_id', 'type', 'notes'];

    protected $casts = [
        'type' => StudentStatus::class,
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(HomeroomTeacher::class, 'homeroom_teacher_id');
    }

    public function bk()
    {
        return $this->belongsTo(User::class, 'bk_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Wali kelas pada baris ini: yang dipilih saat penanganan, atau wali kelas siswa. */
    public function homeroomName(): ?string
    {
        return $this->teacher?->name ?? $this->student?->homeroomTeacher?->name;
    }

    /**
     * Guru BK pada baris ini. Penanganan wali kelas belum melibatkan guru BK.
     * Untuk tindakan BK, SP, dan dropout dipakai guru BK yang memproses siswa itu.
     */
    public function bkName(): ?string
    {
        if ($this->type === StudentStatus::WaliKelas) {
            return null;
        }

        return $this->bk?->name ?? $this->student?->bk?->name;
    }
}