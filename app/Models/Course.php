<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'schedule',
        'created_by',
        'faculty_name',
        'teacher_name',
        'batch'
    ];

    // The admin who created the course
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Enrollments in this course
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    // Lecturer assignments for this course
    public function lecturerAssignments()
    {
        return $this->hasMany(CourseLecturerAssignment::class);
    }

    // Chat groups related to this course
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    // Assignments for this course
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Course-related messages (if applicable)
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}