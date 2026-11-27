<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasRoles, HasUuids, Notifiable;

    public const SUPER_ADMIN_ROLE = 'super-admin';

    public const ADMIN_ROLES = [self::SUPER_ADMIN_ROLE, 'Administrator'];

    public function isAdministrator(): bool
    {
        return $this->hasRole(self::ADMIN_ROLES, 'web');
    }

    protected $fillable = [
        'username', 'name', 'email', 'phone_number', 'employee_code',
        'organization_id', 'organization_code', 'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
        ];
    }
}
