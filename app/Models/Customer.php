<?php

namespace App\Models;

use App\Enums\CustomerGender;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'photo',
        'email',
        'phone',
        'gender',
        'dob',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
        'photo' => 'array',
        'gender' => CustomerGender::class,
    ];

    /**
     * Interact with the customer's email.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    /**
     * Interact with the customer's password.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => bcrypt($value),
        );
    }
}
