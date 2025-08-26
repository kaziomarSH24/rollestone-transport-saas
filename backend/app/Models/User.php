<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // Use the correct trait here
    use HasFactory, Notifiable, HasRoles, HasApiTokens, BelongsToCompany;


    protected string $guard_name = 'web';

    // Force Spatie Permission to always use the 'web' guard for this model
    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'verification_token',
        'otp_expires_at',
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
            'password' => 'hashed',
        ];
    }


    //avatar url
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {

                if ($value) {
                    return Storage::disk('public')->url($value);
                }
                return 'https://ui-avatars.com/api/?background=random&format=svg&name=' . urlencode($this->name);
            },
        );
    }

    /**
     * A user has one wallet.
     */
    public function wallet() : HasOne
    {
        return $this->hasOne(\App\Models\PassengerWallet::class);
    }

    //driver relationship
    public function driver() : HasOne
    {
        return $this->hasOne(Driver::class);
    }

    //company relationship
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

     public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Trip alerts relationship
    public function tripAlerts(): HasMany
    {
        return $this->hasMany(TripAlert::class);
    }


    //relationship with device tokens
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

}
