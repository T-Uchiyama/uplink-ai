<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'member_code',
        'name',
        'email',
        'password',
        'upline_member_id',
        'rank_name',
        'role',
        'status',
        'joined_at',
        'left_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function upline()
    {
        return $this->belongsTo(Member::class, 'upline_member_id');
    }

    public function downlines()
    {
        return $this->hasMany(Member::class, 'upline_member_id');
    }

    public function taskGenerationRequests()
    {
        return $this->hasMany(TaskGenerationRequest::class, 'member_id');
    }

    public function requestedTaskGenerationRequests()
    {
        return $this->hasMany(TaskGenerationRequest::class, 'requested_by_member_id');
    }

    public function generatedTasks()
    {
        return $this->hasMany(GeneratedTask::class, 'member_id');
    }
}
