<?php

namespace App\Services\Passenger;

use App\Services\BaseService;
use App\Models\TripAlert;

class AlertService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = TripAlert::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }
}
