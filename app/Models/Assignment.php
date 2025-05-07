<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'title',
        'description',
        'due_date',
        'links',
    ];

    // The course for which the assignment is created
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // The lecturer who created the assignment
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    // Submissions related to this assignment
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }
}
