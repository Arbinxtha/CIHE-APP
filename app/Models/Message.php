<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'course_id',
        'sender_id',
        'receiver_id',
        'group_id',
        'content',
        'status',
    ];

    // Optional: The course this message is associated with
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // The sender of the message
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // For individual messages: the receiver
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // For group messages: the group
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
