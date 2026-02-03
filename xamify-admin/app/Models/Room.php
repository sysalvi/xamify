<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'session_label',
        'duration_seconds',
    ];

    public function exams()
    {
        return $this->belongsToMany(Exam::class);
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }
}
