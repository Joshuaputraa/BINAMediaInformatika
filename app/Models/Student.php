<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis', 'name', 'status', 'bk_user_id', 'bk_handled_at', 'graduated_at', 'homeroom_teacher_id', 'classroom_id',
    ];

    protected $casts = [
        'status' => StudentStatus::class,
        'bk_handled_at' => 'datetime',
        'graduated_at' => 'datetime',
    ];

    protected $appends = ['bk_handled', 'wali_handled', 'sp_level'];

    public function handlings()
    {
        return $this->hasMany(Handling::class)->latest();
    }

    public function bk()
    {
        return $this->belongsTo(User::class, 'bk_user_id');
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(HomeroomTeacher::class, 'homeroom_teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function isProblem(): bool
    {
        return $this->status->isProblem();
    }

    public function isDropout(): bool
    {
        return $this->status->isFinal();
    }

    public function isGraduated(): bool
    {
        return $this->status->isGraduated();
    }

    /** Dikirim ke frontend sebagai `bk_handled`: guru BK yang menaungi SAAT INI sudah menangani. */
    public function getBkHandledAttribute(): bool
    {
        return $this->hasBeenHandledByBk();
    }

    /** Guru BK saat ini sudah mencatat penanganan untuk siswa ini. Penanganan guru BK sebelumnya tidak dihitung. */
    public function hasBeenHandledByBk(): bool
    {
        if (! $this->bk_user_id) {
            return false;
        }

        $mine = fn ($h) => $h->type === StudentStatus::Bk && (int) $h->user_id === (int) $this->bk_user_id;

        return $this->relationLoaded('handlings')
            ? $this->handlings->contains($mine)
            : $this->handlings()
                ->where('type', StudentStatus::Bk->value)
                ->where('user_id', $this->bk_user_id)
                ->exists();
    }

    /** Ada rujukan yang ditujukan ke guru BK yang menaungi siswa ini saat ini. */
    public function isReferredToBk(): bool
    {
        if (! $this->bk_user_id) {
            return false;
        }

        $referred = fn ($h) => $h->type === StudentStatus::Bk && (int) $h->bk_user_id === (int) $this->bk_user_id;

        return $this->relationLoaded('handlings')
            ? $this->handlings->contains($referred)
            : $this->handlings()
                ->where('type', StudentStatus::Bk->value)
                ->where('bk_user_id', $this->bk_user_id)
                ->exists();
    }

    /** Dikirim ke frontend sebagai `wali_handled`: wali kelas SAAT INI sudah menangani. */
    public function getWaliHandledAttribute(): bool
    {
        return $this->hasBeenHandledByWaliKelas();
    }

    public function hasBeenHandledByWaliKelas(): bool
    {
        if (! $this->homeroom_teacher_id) {
            return false;
        }

        $mine = fn ($h) => $h->type === StudentStatus::WaliKelas
            && (int) $h->homeroom_teacher_id === (int) $this->homeroom_teacher_id;

        return $this->relationLoaded('handlings')
            ? $this->handlings->contains($mine)
            : $this->handlings()
                ->where('type', StudentStatus::WaliKelas->value)
                ->where('homeroom_teacher_id', $this->homeroom_teacher_id)
                ->exists();
    }

    /** Dikirim ke frontend sebagai `sp_level` (0 = belum pernah SP). */
    public function getSpLevelAttribute(): int
    {
        return $this->spLevel();
    }

    /** Level SP tertinggi yang pernah diberikan (0 sampai 3), kumulatif lintas kelas. */
    public function spLevel(): int
    {
        $types = $this->relationLoaded('handlings')
            ? $this->handlings->map(fn ($h) => $h->type->value)
            : $this->handlings()->get(['type'])->map(fn ($h) => $h->type->value);

        return $types->map(fn ($t) => match ($t) {
            'sp1' => 1,
            'sp2' => 2,
            'sp3' => 3,
            default => 0,
        })->max() ?? 0;
    }

    public function scopeProblem($query)
    {
        return $query->whereNotIn('status', [StudentStatus::Normal->value, StudentStatus::Graduated->value]);
    }

    /** Aturan siapa boleh mengisi penanganan untuk siswa ini. */
    public function canBeHandledBy(User $user): bool
    {
        if ($this->isDropout() || $this->isGraduated()) {
            return false;
        }

        return match ($user->role) {
            'kesiswaan' => true,
            'bk' => $this->status === StudentStatus::Bk
                && (int) $this->bk_user_id === (int) $user->id
                && $this->isReferredToBk()
                && ! $this->hasBeenHandledByBk(),
            default => false,
        };
    }
}