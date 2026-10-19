<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['nis', 'name', 'status', 'bk_user_id', 'bk_handled_at', 'homeroom_teacher_id'];

    protected $casts = [
        'status' => StudentStatus::class,
        'bk_handled_at' => 'datetime',
    ];

    protected $appends = ['bk_handled', 'wali_handled'];

    public function handlings()
    {
        return $this->hasMany(Handling::class)->latest();
    }

    public function bk()
    {
        return $this->belongsTo(User::class, 'bk_user_id');
    }

    public function isProblem(): bool
    {
        return $this->status->isProblem();
    }

    public function isDropout(): bool
    {
        return $this->status->isFinal();
    }

    /** Dikirim ke frontend sebagai `bk_handled` (true/false). */
    public function getBkHandledAttribute(): bool
    {
        return $this->hasBeenHandledByBk();
    }

    public function hasBeenHandledByBk(): bool
    {
        if ($this->bk_handled_at !== null) {
            return true;
        }

        // Cadangan untuk data lama: cek riwayat penanganan oleh akun guru BK.
        $isBkHandling = fn ($h) => $h->type === StudentStatus::Bk && $h->user?->role === 'bk';

        if ($this->relationLoaded('handlings')) {
            return $this->handlings->contains($isBkHandling);
        }

        return $this->handlings()
            ->where('type', StudentStatus::Bk->value)
            ->whereHas('user', fn ($q) => $q->where('role', 'bk'))
            ->exists();
    }

    public function scopeProblem($query)
    {
        return $query->where('status', '!=', StudentStatus::Normal->value);
    }

    /** Aturan siapa boleh mengisi penanganan untuk siswa ini. */
    public function canBeHandledBy(User $user): bool
    {
        if ($this->isDropout()) {
            return false;
        }

        return match ($user->role) {
            'kesiswaan' => true,
            'bk' => $this->status === StudentStatus::Bk
                && (int) $this->bk_user_id === (int) $user->id
                && ! $this->hasBeenHandledByBk(),
            default => false,
        };
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(HomeroomTeacher::class, 'homeroom_teacher_id');
    }

    /** Dikirim ke frontend sebagai `wali_handled` (true/false). */
    public function getWaliHandledAttribute(): bool
    {
        return $this->hasBeenHandledByWaliKelas();
    }

    public function hasBeenHandledByWaliKelas(): bool
    {
        if ($this->relationLoaded('handlings')) {
            return $this->handlings->contains(fn ($h) => $h->type === StudentStatus::WaliKelas);
        }

        return $this->handlings()
            ->where('type', StudentStatus::WaliKelas->value)
            ->exists();
    }
}