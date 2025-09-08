<?php

namespace App\Services\Admin;

use App\Services\BaseService;
use App\Models\User as Passenger;

class PassengerService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Passenger::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }

    //dashboard stats
    public function getPassengerStats(): array
    {
        $model = new $this->modelClass;
        $totalPassengers = $model->role('Passenger')->count();
        $activePassengers = $model->role('Passenger')->where('status', 'active')->count();
        $suspendedPassenger = $model->role('Passenger')->where('status', 'suspended')->count();
        $totalBlance = $model->role('Passenger')->with('wallet')->get()->sum(function ($passenger) {
            return $passenger->wallet ? $passenger->wallet->balance : 0;
        });
        $activeCard = $model->role('Passenger')->where('status', 'active')->with('paymentMethods')->get()->sum(function ($passenger) {
            return $passenger->paymentMethods ? $passenger->paymentMethods->count() : 0;
        });
        return [
            'total_passengers' => $totalPassengers,
            'active_passengers' => $activePassengers,
            'suspended_passengers' => $suspendedPassenger,
            'total_balance' => $totalBlance,
            'active_cards' => $activeCard,
        ];
    }
}
