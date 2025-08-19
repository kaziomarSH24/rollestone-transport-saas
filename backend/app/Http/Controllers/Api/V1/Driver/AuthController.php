<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request for a driver.
     */
    public function login(Request $request)
    {
        $request->validate([
            'staff_number' => 'required|string',
            'pin_code' => 'required|string',
        ]);

        $driver = Driver::where('staff_number', $request->staff_number)->first();

        if (!$driver || !Hash::check($request->pin_code, $driver->pin_code)) {
            throw ValidationException::withMessages([
                'staff_number' => ['Invalid driver ID or pin code.'],
            ]);
        }

        // If the driver exists and the pin code matches, generate a token
        $user = $driver->user;
        $token = $user->createToken('driver-auth-token')->plainTextToken;
        $company = $user->load('company:id,zello_channel')->company; // Load the company with Zello
        $user->makeHidden(['company']);

        return response_success('Driver login successful.', [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'driver_details' => $driver,
            'zello_channel' => $user->company->zello_channel ?? null,
        ]);
    }
}
