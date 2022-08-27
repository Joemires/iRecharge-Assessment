<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
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
    ];

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function deposit(Float|Int $amount, ?Array $meta, Bool $confirmed = true)
    {
        $this->wallet_balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'uuid' => Str::orderedUuid(),
            'amount' => abs($amount),
            'confirmed' => $confirmed,
            'meta' => $meta
        ]);
    }

    public function withdraw(Float|Int $amount, ?Array $meta, Bool $confirmed = true)
    {
        $this->wallet_balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'uuid' => Str::orderedUuid(),
            'amount' => -1 * abs($amount),
            'confirmed' => $confirmed,
            'meta' => $meta
        ]);
    }
}
