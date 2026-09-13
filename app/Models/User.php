<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'avatar',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true)
            || $this->hasAnyRole(['admin', 'superadmin']);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_user')->withTimestamps();
    }

    public function addresses(){return $this->hasMany(Address::class);}
    public function orders(){return $this->hasMany(Order::class);}

    public function hasMenuAccess(string $route): bool
    {
        if ($this->isAdmin()) return true;
        return $this->menus()->where('route', $route)->exists();
    }
}
