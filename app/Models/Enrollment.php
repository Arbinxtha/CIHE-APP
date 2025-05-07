<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'status',
    ];

    // The course in which the student enrolled
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // The student who enrolled
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
