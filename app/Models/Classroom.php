<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['grade_id', 'major_id', 'number'];

    protected $appends = ['name'];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /** Nama lengkap kelas, contoh: "X RPL 1". */
    public function getNameAttribute(): string
    {
        return trim("{$this->grade?->name} {$this->major?->name} {$this->number}");
    }
}