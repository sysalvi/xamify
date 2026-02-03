<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'exam_link',
        'exam_token',
        'is_active',
        'available_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'available_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }
}
