<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Company extends Model
{
    use HasFactory, HasRoles;

    // Ensure Spatie Permission resolves guard as 'web' for this model
    protected $guard_name = 'web';

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'stripe_secret_key' => 'encrypted', // Automatically encrypts and decrypts
            'stripe_webhook_secret' => 'encrypted', // Automatically encrypts and decrypts
        ];
    }

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    //company admin user
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
