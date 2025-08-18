<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\DriverStoreRequest;
use App\Http\Requests\Admin\DriverUpdateRequest;
use App\Http\Resources\DriverResource;
use App\Services\Admin\DriverService;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;

class DriverController extends BaseController
{
    use FileUploadTrait;

    protected DriverService $driverService;

    public function __construct(DriverService $driverService)
    {
       $this->driverService = $driverService;

        // Apply authorization middleware for different actions
        $this->middleware('can:view drivers')->only(['index', 'show']);
        $this->middleware('can:create drivers')->only(['store']);
        $this->middleware('can:edit drivers')->only(['update']);
        $this->middleware('can:delete drivers')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $perPage = $request->get('per_page', 15);

        $drivers = $this->driverService->getAll(['user'], $perPage);
        if ($drivers->isEmpty()) {
            return response_error('No drivers found.', [], 404);
        }

        return DriverResource::collection($drivers);
        } catch (\Exception $e) {
            return response_error('Failed to fetch drivers.', ['error' => $e->getMessage()], 500);
        }

    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(DriverStoreRequest $request)
    {
        $driverUser = $this->driverService->createDriver($request->validated());
        return response_success('Driver created successfully.', $driverUser, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $driver = $this->driverService->getById($id, ['user']);

        if (!$driver) {
            return response_error('Driver not found.', [], 404);
        }
        return new DriverResource($driver);
        }catch (\Exception $e) {
            return response_error('Failed to fetch driver.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DriverUpdateRequest $request, string $id)
    {
        $driver = $this->driverService->updateDriver((int)$id, $request->validated());
        return response_success('Driver updated successfully.', new DriverResource($driver));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $driver = $this->driverService->getById($id, ['user:id,avatar']);
            $user = $driver->user;

          //remove avatar if exists
        if (!$driver) {
            return response_error('Driver not found.', [], 404);
        }

        if ($user->avatar) {
            $this->deleteFile($user->getRawOriginal('avatar'));
        }
        //delete user and driver
        $user->delete();
        // $driver->delete();

        return response_success('Driver deleted successfully.');
        } catch (\Exception $e) {
            return response_error('Failed to delete driver.', ['error' => $e->getMessage()], 500);
        }
    }
}
