<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'group_name',
    ];

    // The course associated with the group
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Members in this chat group
    public function members()
    {
        return $this->hasMany(GroupMember::class);
    }

    // Group messages
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
