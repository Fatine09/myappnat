<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function returns()
    {
        return $this->hasMany(Productsreturn::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isVendeur()
    {
        return $this->role === 'vendeur';
    }

    public function isClient()
    {
        return $this->role === 'client';
    }
    public function addresses()
    {
    return $this->hasMany(Address::class);
    }

public function reviews()
    {
    return $this->hasMany(Review::class);
    }

public function cart()
    {
    return $this->hasOne(Cart::class);
    }

public function notifications()
    {
    return $this->hasMany(Notification::class);
    }
};