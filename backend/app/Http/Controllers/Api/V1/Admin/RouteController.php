<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RouteStoreRequest;
use App\Http\Resources\Admin\RouteIndexResource;
use App\Http\Resources\Admin\RouteResource;
use App\Models\Route;
use App\Services\RouteService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    protected RouteService $routeService;

    public function __construct(RouteService $routeService)
    {
        $this->routeService = $routeService;
        //middleware for authorization
        $this->middleware('can:view routes')->only(['index', 'show']);
        $this->middleware('can:create routes')->only(['store']);
        $this->middleware('can:edit routes')->only(['update']);
        $this->middleware('can:delete routes')->only(['destroy']);
    }

    //route state
    public function getRouteStates()
    {
        $states = Route::ROUTE_STATES;
        return response_success('Route states retrieved successfully.', $states);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routes = $this->routeService->getAll(['stops', 'fares', 'trips']);
        if ($routes->isEmpty()) {
            return response_error('No routes found.', [], 404);
        }

        return RouteIndexResource::collection($routes)
            ->additional([
                'ok' => true,
                'message' => 'Routes retrieved successfully.'
            ])
            ->response()
            ->setStatusCode(200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(RouteStoreRequest $request)
    {
        $route = $this->routeService->createRoute($request->validated());
        return response_success('Route created successfully.', $route, 201);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $route = $this->routeService->getById($id, ['stops', 'fares']);
        if (!$route) {
            return response_error('Route not found.', [], 404);
        }

        return new RouteResource($route);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RouteStoreRequest $request, int $id)
    {
        $route = $this->routeService->updateRoute($id, $request->validated());
        return response_success('Route updated successfully.', $route);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $route = $this->routeService->getById($id, ['stops', 'fares']);
            if (!$route) {
                return response_error('Route not found.', [], 404);
            }

            // Delete the route
            $route->delete();

            return response_success('Route deleted successfully.');
        } catch (\Exception $e) {
            return response_error('Failed to delete route.', ['error' => $e->getMessage()], 500);
        }
    }
}
