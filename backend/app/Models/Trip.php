<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use BelongsToCompany;
    protected $guarded = ['id'];


    /**
     * The "boot" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($trip) {

            if (is_null($trip->trip_number)) {
                $latestTrip = Trip::where('company_id', $trip->company_id)
                    ->whereNotNull('trip_number')
                    ->orderBy('trip_number', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($latestTrip && is_numeric($latestTrip->trip_number)) {
                    $nextNumber = (int)$latestTrip->trip_number + 1;
                }
                $trip->trip_number = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $trip->save();
            }
        });
    }

    //relationships with route and company
    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    //relationships with journey
    public function journeys()
    {
        return $this->hasMany(Journey::class);
    }

    //relationships with stops
    public function stops()
    {
        return $this->hasManyThrough(RouteStop::class, Route::class);
    }
}
