<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;

class ProfileController extends Controller
{
    use FileUploadTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');
        //check user role -> passenger
        if($user->hasRole('Passenger')){
            $user->load('wallet', 'transactions');
        }
        $formattedUser = new UserResource($user);
        return response_success('User data fetched successfully.', $formattedUser);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();
            $validatedData = $request->validated();


            if ($request->hasFile('avatar')) {
                $avatarPath = $this->handleFileUpload($request, 'avatar', 'avatars');
                $this->deleteFile($user->getRawOriginal('avatar'));

                $validatedData['avatar'] = $avatarPath;
            }

            $updatedUser = $this->authService->updateProfile($user, $validatedData);

            return response_success('Profile updated successfully.', $updatedUser);
        } catch (\Exception $e) {
            return response_error('Failed to update profile.', ['error' => $e->getMessage()], 500);
        }
    }
}
