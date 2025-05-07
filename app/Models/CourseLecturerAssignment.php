<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLecturerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lecturer_id',
    ];

    // The course being assigned
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // The lecturer assigned to the course
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
