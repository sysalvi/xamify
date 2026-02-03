<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = [
        'student_name',
        'student_class',
        'exam_id',
        'room_id',
        'device_id',
        'access_code',
        'status',
        'last_ping_at',
        'violation_count',
        'last_violation_reason',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_ping_at' => 'datetime',
            'violation_count' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
