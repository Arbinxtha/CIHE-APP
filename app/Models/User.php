<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

        // Courses created by an admin
        public function coursesCreated()
        {
            return $this->hasMany(Course::class, 'created_by');
        }
    
        // Enrollments if the user is a student
        public function enrollments()
        {
            return $this->hasMany(Enrollment::class, 'student_id');
        }
    
        // Lecturer assignments if the user is a lecturer
        public function lecturerAssignments()
        {
            return $this->hasMany(CourseLecturerAssignment::class, 'lecturer_id');
        }
    
        // Group memberships for chat groups
        public function groupMembers()
        {
            return $this->hasMany(GroupMember::class, 'user_id');
        }
    
        // Messages sent by the user
        public function messagesSent()
        {
            return $this->hasMany(Message::class, 'sender_id');
        }
    
        // Individual messages received by the user
        public function messagesReceived()
        {
            return $this->hasMany(Message::class, 'receiver_id');
        }
    
        // Assignment submissions by the student
        public function submissions()
        {
            return $this->hasMany(Submission::class, 'student_id');
        }
    
        // Security logs associated with the user
        public function securityLogs()
        {
            return $this->hasMany(SecurityLog::class);
        }
                 
}
