<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
    ];

    // The group this membership belongs to
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // The user who is a member of the group
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}