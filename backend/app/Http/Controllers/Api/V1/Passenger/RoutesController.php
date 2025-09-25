<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Passenger\RouteDetailResource;
use App\Http\Resources\Passenger\RouteResource;
use App\Http\Resources\Passenger\RouteSummaryResource;
use App\Models\Route;
use App\Services\Passenger\RouteListService;
use App\Services\Passenger\RouteScheduleService;
use Illuminate\Http\Request;

class RoutesController extends Controller
{
    protected RouteListService $listService;
    protected RouteScheduleService $scheduleService;
    public function __construct(RouteListService $listService, RouteScheduleService $scheduleService)
    {
        $this->listService = $listService;
        $this->scheduleService = $scheduleService;
        // Middleware for authorization
        $this->middleware('can:view routes')->only(['index', 'show']);
    }
    //available Routes for passengers
    public function index(Request $request)
    {
        // Fetch routes based on the company_id from the request
        $status = $request->query('status', false);
        try {

            $routes = $this->listService->getRoutesWithSummary($status);
            return RouteSummaryResource::collection($routes);
            if ($routes->isEmpty()) {
                return response()->json(['message' => 'No active routes found'], 404);
            }

            return RouteResource::collection($routes);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching routes: ' . $e->getMessage()], 500);
        }
    }

    //show route details
    public function show($routeId)
    {
       try{
        $route = Route::with([
            'stops',
            'trips',
            'fares',
            'journeys' => function ($query) {
            $query->where('status', 'ongoing');
            }
        ])->findOrFail($routeId);
        // return $route;
         return new RouteDetailResource($route);
       }catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching route details: ' . $e->getMessage()], 500);
        }
    }
}
