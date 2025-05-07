<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
    ];

    // The user associated with the security log entry
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
