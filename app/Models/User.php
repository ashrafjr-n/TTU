<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'identifier',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // العلاقات
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    // Helper methods مفيدة لاحقًا بالكود (تسهل قراءة الشروط)
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

        public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}