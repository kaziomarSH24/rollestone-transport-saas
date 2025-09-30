<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Route;

class RouteService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Route::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }



    public function createRoute(array $data): Route
    {
        //Separate main route data from relational data
        $relations = [
            'stops' => $data['stops'],
            'fares' => $this->prepareFaresData($data['fares']),
        ];

        $routeData = [
            'name' => $data['name'],
            'route_prefix' => $data['route_prefix'],
            'google_map_link' => $data['google_map_link'],
            'status' => $data['status'],
        ];

        $route = $this->create($routeData, $relations);

        return $route->load(['stops', 'fares']);
    }

    public function updateRoute(int $routeId, array $data): Route
    {
        //Separate main route data from relational data
        $relations = [
            'stops' => $data['stops'],
            'fares' => $this->prepareFaresData($data['fares']),
        ];

        $routeData = [
            'name' => $data['name'],
            'route_prefix' => $data['route_prefix'],
            'google_map_link' => $data['google_map_link'],
            'status' => $data['status'],
        ];
        
        $route = $this->update($routeId, $routeData, $relations);

        return $route->load(['stops', 'fares']);
    }

    /**
     * Helper method to transform the incoming fare data into the correct format for the database.
     */
    private function prepareFaresData(array $fares): array
    {
        $fareDataToInsert = [];
        foreach ($fares as $fare) {
            // Create one record for Cash payment
            $fareDataToInsert[] = [
                'passenger_type' => $fare['passenger_type'],
                'payment_method' => 'Cash',
                'amount' => $fare['cash_amount'],
            ];
            // Create another record for User App (Wallet) payment
            $fareDataToInsert[] = [
                'passenger_type' => $fare['passenger_type'],
                'payment_method' => 'Wallet',
                'amount' => $fare['app_amount'],
            ];
        }
        return $fareDataToInsert;
    }
}
