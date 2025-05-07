<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'content',
        'file_url',
        'status',
        'feedback',
    ];

    // The assignment that this submission is for
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    // The student who submitted the assignment
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
