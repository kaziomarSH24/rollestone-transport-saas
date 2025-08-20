<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    protected WalletService $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
        //driver role middleware
        $this->middleware('role:Driver');
    }


}
