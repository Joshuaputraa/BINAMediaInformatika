<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['name', 'bk_user_id'];

    public function bk()
    {
        return $this->belongsTo(User::class, 'bk_user_id');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, Classroom::class);
    }
}