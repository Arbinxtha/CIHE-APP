<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = ['user_ids', 'message', 'scheduled_at', 'is_sent','user_id'];

    protected $casts = [
        'user_ids' => 'array',
        'scheduled_at' => 'datetime',
    ];
}
