<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Transaction extends Model
{
    use BelongsToCompany;
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with the transaction.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    //relattionships with journey and payment method
    public function journey()
    {
        return $this->belongsTo(Journey::class);
    }
}
