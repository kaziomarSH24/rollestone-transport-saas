<?php

namespace App\Services;

use App\Services\BaseService;
use App\Models\Company;
use App\Models\User;
use App\Services\UserService;
use App\Services\AuthService;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;

class CompanyService extends BaseService
{
    use FileUploadTrait;
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Company::class;

    /**
     * The Service instance.
     *
     * @var UserService
     * @var AuthService
     */
    protected UserService $userService;
    protected AuthService $authService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();

        $this->userService = $userService;
        $this->authService = $authService;
    }


    //create a new Company which includes creating a User record first.
    public function createCompany(array $data): Company
    {

        return DB::transaction(function () use ($data) {
            if (empty($data['username'])) {
                $data['username'] = $this->authService->generateUniqueUsername($data['name']);
            }

            $companyFiled = ['company_name', 'contact_email', 'subdomain', 'status'];
            $companyData = array_intersect_key($data, array_flip($companyFiled));
            $userFiled = ['name', 'username', 'email', 'password', 'phone_number', 'avatar'];
            $userData = array_intersect_key($data, array_flip($userFiled));
            //file upload
            if (isset($data['avatar'])) {
                $userData['avatar'] = $this->handleFileUpload(request(), 'avatar', 'avatars');
            }

            // Create the company first
            $company = $this->create($companyData);
            // Assign system access permissions to the new company.
            if (!empty($data['system_access'])) {
                $company->givePermissionTo($data['system_access']);
            }
            // Set the company_id on the user data
            $userData['company_id'] = $company->id;
            //email verification
            $userData['email_verified_at'] = now();
            // Create the user
             $adminUser = User::create($userData);

             $userId = $adminUser->id;
             //update company user_id field
             $company->user_id = $userId;
             $company->save();
            $adminUser->assignRole('CompanyAdmin');


            return $company->load('user');
        });
    }

    // Update an existing company and its associated user.
    public function updateCompany(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data) {
            // Update the company fields
            $company->update($data);

            // If system access permissions are provided, update them
            if (!empty($data['system_access'])) {
                $company->syncPermissions($data['system_access']);
            }

            // Update the associated user
            $userData = array_intersect_key($data, array_flip(['name', 'username', 'email', 'phone_number', 'avatar']));
            if (isset($data['avatar'])) {
                //remove old avatar if exists
                if ($company->user->avatar) {
                    $this->deleteFile($company->user->avatar);
                }
                // Handle file upload for the new avatar
                $userData['avatar'] = $this->handleFileUpload(request(), 'avatar', 'avatars');
            }
            $company->user->update($userData);

            return $company->load('user');
        });
    }
}
