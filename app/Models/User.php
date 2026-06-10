<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (! isset($user->role)) {
                $user->role = UserRole::User;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isDev(): bool
    {
        return $this->role === UserRole::Dev;
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class);
    }

    public function creditCards()
    {
        return $this->belongsToMany(
            CreditCard::class,
            'credit_card_user',
            'user_id',
            'credit_card_id'
        );
    }


    public function networkIds(): array
    {
        return $this->networkUsers()->pluck('id')->all();
    }

    public function relatedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'user_relations',
            'user_id',
            'related_user_id'
        );
    }

    public function relatedToMe()
    {
        return $this->belongsToMany(
            User::class,
            'user_relations',
            'related_user_id',
            'user_id'
        );
    }

    // Conjunto: eu + todos que têm vínculo comigo (bidirecional)
    public function networkUsers()
    {
        return $this->relatedUsers
            ->merge($this->relatedToMe)
            ->push($this)
            ->unique('id')
            ->values();
    }
}
