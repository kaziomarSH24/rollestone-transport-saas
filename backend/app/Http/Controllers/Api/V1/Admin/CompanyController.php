<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\CompanyStoreRequset;
use App\Http\Requests\Admin\CompanyUpdateRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CompanyController extends BaseController
{
    /**
     * The service instance.
     *
     * @var CompanyService
     */
    protected CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
        // Apply authorization middleware for different actions
        $this->middleware('can:view companies')->only(['index', 'show']);
        $this->middleware('can:create companies')->only(['store']);
        $this->middleware('can:edit companies')->only(['update']);
        $this->middleware('can:delete companies')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch all companies with pagination
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', null);
        if ($search) {
            $queryCallback = function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('email', 'like', "%$search%");
                })->orWhere('company_name', 'like', "%$search%");
            };
        } else {
            $queryCallback = null;
        }
        $companies = $this->companyService->getAll(['user'], $perPage, $queryCallback);

        if ($companies->isEmpty()) {
            return response_error('No companies found.', [], 404);
        }

        return CompanyResource::collection($companies)
            ->additional([
                'ok' => true,
                'message' => 'Companies retrieved successfully.'
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyStoreRequset $request)
    {
        $companiesUser = $this->companyService->createCompany($request->validated());
        return response_success('Company created successfully.', $companiesUser, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Fetch the company by ID with its user relationship
        $company = $this->companyService->getById($id, ['user']);
        if (!$company) {
            return response_error('Company not found.', [], 404);
        }
        return new CompanyResource($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyUpdateRequest $request, string $id)
    {
        $company = Company::findOrFail((int)$id);
        $companies = $this->companyService->updateCompany($company, $request->validated());
        return response_success('Company updated successfully.', new CompanyResource($companies));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $company = $this->companyService->getById($id, ['user:id,company_id,avatar']);

            $user = $company->user;

            if ($user->hasRole('SuperAdmin')) {
                return response_error(
                    'A company containing a SuperAdmin cannot be deleted.',
                    [],
                    403
                );
            }
            //remove avatar if exists
            if ($user->avatar) {
                $this->companyService->deleteFile($user->avatar);
            }
            //delete user and company
            $user->delete();
            $company->delete();
            return response_success('Company deleted successfully.', [], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response_error('Company not found.', [], 404);
        } catch (\Exception $e) {
            return response_error('Failed to delete company.', ['error' => $e->getMessage()], 500);
        }
    }
}
