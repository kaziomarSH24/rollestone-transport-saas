<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LiveDashboardResource;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Exception;


class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
         $this->middleware('can:view dashboard');
    }

    //dashboard stats
     public function getStats()
    {
        $stats = $this->dashboardService->getDashboardStats();
        return response_success('Dashboard stats retrieved successfully.', $stats);
    }

    /**
     * Get live data for the admin dashboard.
     */
    public function getLiveData(Request $request)
    {
        $request->validate([
            'filter' => 'sometimes|in:all,ongoing,completed,blocked,cancelled',
        ]);
        $filter = $request->query('filter', 'all'); // 'all', 'ongoing', 'completed'
        $liveJourneys = $this->dashboardService->getLiveDashboardData($filter);
        if ($liveJourneys->isEmpty()) {
            return response_error('No live trips found.', [], 404);
        }
        return LiveDashboardResource::collection($liveJourneys);
    }
}
