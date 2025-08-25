<?php
namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Route extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = ['id'];

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function fares(): HasMany
    {
        return $this->hasMany(Fare::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    //relation with journeys through trips
    public function journeys(): HasManyThrough
    {
        return $this->hasManyThrough(Journey::class, Trip::class);
    }

     protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->route_prefix ? $this->route_prefix . ' ' : '') . $this->name,
        );
    }
}
