<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Get the transaction history for the authenticated user.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $filterType = $request->query('type');

        $transactions = $this->transactionService->getHistoryForUser($request->user(), $perPage, $filterType);
        if ($transactions->isEmpty()) {
            return response_error('No transactions found.', [], 404);
        }
        return TransactionResource::collection($transactions);
    }
}
