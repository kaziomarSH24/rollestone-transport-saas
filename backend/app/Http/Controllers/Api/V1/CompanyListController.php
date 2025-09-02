<?php

namespace App\Http\Controllers\api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyListController extends Controller
{
    public function index(Request $request)
    {
        $companies = DB::table('companies')
            ->join('users', 'companies.user_id', '=', 'users.id')
            ->whereNotNull('users.email_verified_at')
            ->select(
                'companies.id as company_id',
                'companies.company_name',
                'companies.contact_email',
                'users.id as owner_id',
                'users.name as owner_name',
                'users.email as owner_email',
                'users.avatar as company_logo',
                'users.address as owner_address'
            )
            ->get();
            $companies = $companies->map(function ($company) {
                if ($company->company_logo) {
                    $company->company_logo = asset('storage/' . $company->company_logo);
                } else {
                    // $company->company_logo = 'https://ui-avatars.com/api/?background=random&color=ffffff&bold=true&rounded=true&size=512&format=png&name=' . urlencode($company->company_name);
                    $company->company_logo = "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQVFTdLWKt1qYfNdXPz7FnJokLDgL8l1f6LJaMBnYveRfiNrPIAzE6mw6coLIasUY1f-2Q&usqp=CAU";
                }
                return $company;
            });
        if ($companies->isEmpty()) {
            return response_error('No companies found', [], 404);
        }
        return response_success('Company list retrieved successfully', $companies);
    }
}
