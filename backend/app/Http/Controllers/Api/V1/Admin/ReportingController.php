<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportingController extends Controller
{
    protected $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Get revenue by route report.
     */
    public function revenueByRoute(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'filter' => 'sometimes|in:daily,weekly,monthly',
        ]);
        if ($validated->fails()) {
            return response_error($validated->errors()->first(), $validated->errors()->toArray(), 422);
        }
        $validated = $validated->validated();
        $filter = $validated['filter'] ?? 'monthly';

        $reportData = $this->reportingService->getRevenueByRoute($filter);

        return response_success('Revenue by route report generated successfully.', $reportData);
    }

    /**
     * Get monthly revenue trends report.
     */
    public function monthlyTrends()
    {
        $reportData = $this->reportingService->getMonthlyRevenueTrends();
        return response_success('Monthly trends report generated successfully.', $reportData);
    }
    /**
     * Get cash reconciliation data for a specific date.
     */
    public function getCashReconciliation(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);
        if ($validated->fails()) {
            return response_error($validated->errors()->first(), $validated->errors()->toArray(), 422);
        }
        $validated = $validated->validated();
        $reportData = $this->reportingService->getCashReconciliationData($validated['date']);
        return response_success('Cash reconciliation data retrieved.', $reportData);
    }

    /**
     * Check and save a cash reconciliation entry.
     */
    public function checkCashReconciliation(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'driver_id' => 'required|integer|exists:drivers,id',
            'date' => 'required|date_format:Y-m-d',
            'cash_processed' => 'required|numeric',
            'received_cash' => 'required|numeric|min:0',
        ]);
        if ($validated->fails()) {
            return response_error($validated->errors()->first(), $validated->errors()->toArray(), 422);
        }
        $validated = $validated->validated();

        $reconciliation = $this->reportingService->checkCashReconciliation($validated);
        return response_success('Cash reconciliation checked successfully.', $reconciliation);
    }

    /**
     * Get passenger analytics report.
     */
    public function passengerAnalytics()
    {
        $reportData = $this->reportingService->getPassengerAnalytics();
        return response_success('Passenger analytics generated successfully.', $reportData);
    }

    /**
     * Get route statistics report.
     */
    public function routeStatistics()
    {
        $reportData = $this->reportingService->getRouteStatistics();
        return response_success('Route statistics report generated successfully.', $reportData);
    }
}
