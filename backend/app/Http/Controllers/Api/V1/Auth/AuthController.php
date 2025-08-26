<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Traits\FileUploadTrait;

class AuthController extends Controller
{
    use FileUploadTrait;
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        //avatar image upload
        $imagePath = $this->handleFileUpload($request, 'avatar', 'avatars');
        if ($imagePath) {
            $request->merge(['avatar' => $imagePath]);
        }
        $this->authService->register($request->validated());
        return response_success('User registered. Please check your email for verification.', [], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'nullable|string|max:500'
        ]);

        try {
            $data = $this->authService->login($request->only('email', 'password', 'fcm_token'));
            return response_success('Login successful', $data);
        } catch (ValidationException $e) {
            return response_error($e->getMessage(), $e->errors(), 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response_success('Successfully logged out');
    }
}
