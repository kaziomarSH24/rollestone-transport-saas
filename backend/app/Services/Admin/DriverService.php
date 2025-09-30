<?php

namespace App\Services\Admin;

use App\Models\Driver;
use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService;
use App\Services\BaseService;
use App\Services\UserService;

class DriverService extends BaseService
{
    use FileUploadTrait;

    protected AuthService $authService;
    protected UserService $userService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;

        // Initialize BaseService internals (sets $this->model based on $modelClass)
        parent::__construct();
    }

    /**
     * Define the Eloquent model class managed by this service.
     *
     * @var string
     */
    protected string $modelClass = Driver::class;


    //create a new Driver which includes creating a User record first.
    public function createDriver(array $data): User
    {
        try{
            // Check if username was provided. If not, generate one.
            if (empty($data['username'])) {
                $data['username'] = $this->authService->generateUniqueUsername($data['name']);
            }

            $userData = [
            'company_id' => tenant('id'),
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['pin_code']), // by default, using pin_code as password
            'phone_number' => $data['phone_number'] ?? null,
        ];

        //file upload
        if (isset($data['avatar'])) {
                $userData['avatar'] = $this->handleFileUpload(request(), 'avatar', 'avatars');
            }
        $transactionalCallback = function ($user) use ($data) {
            $user->assignRole('Driver');

            $driverData = [
                'company_id' => tenant('id'),
                'staff_number' => $data['staff_number'],
                'pin_code' => Hash::make($data['pin_code']),
                'license_number' => $data['license_number'],
                'license_expiry_date' => $data['license_expiry_date'],
                'experience_years' => $data['experience_years'],
            ];
            $user->driver()->create($driverData);
        };

        $user = $this->userService->create($userData, [], $transactionalCallback);
        $user->load('driver');
        return $user;

        }catch(\Exception $e) {
            throw new \Exception('Failed to create driver: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing driver and their associated user record.
     */
    public function updateDriver(int $driverId, array $validatedData): Driver
    {

        $driverFields = ['staff_number', 'license_number', 'license_expiry_date','experience_years'];
        $driverUpdates = array_intersect_key($validatedData, array_flip($driverFields));

        if (!empty($validatedData['pin_code'])) {
            $driverUpdates['pin_code'] = Hash::make($validatedData['pin_code']);
        }

        //Prepare data for the related model (User)
        $userFields = ['name', 'username', 'email', 'phone_number'];
        $userUpdates = array_intersect_key($validatedData, array_flip($userFields));

        if (!empty($validatedData['password'])) {
            $userUpdates['password'] = Hash::make($validatedData['password']);
        }

        //Define the transactional callback to update the related User
        $transactionalCallback = function ($driver) use ($userUpdates, $validatedData) {
            $user = $driver->user;
            $oldAvatar = $user->getRawOriginal('avatar');

            // Handle avatar upload
            if (isset($validatedData['avatar'])) {
                $path = $this->handleFileUpload(request(), 'avatar', 'avatars');
                if ($path) {
                    $userUpdates['avatar'] = $path;
                }
            }

            if (!empty($userUpdates)) {
                $user->update($userUpdates);

                // Delete old avatar if a new one was uploaded
                if (array_key_exists('avatar', $userUpdates) && $oldAvatar) {
                    $this->deleteFile($oldAvatar);
                }
            }
        };

        $driver = $this->update($driverId, $driverUpdates, [], $transactionalCallback);
        return $driver->load('user');
    }

}
