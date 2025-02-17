<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Exception;

class BalanceController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $balanceService;

    /**
     * Constructor to inject dependencies
     * 
     * @param BalanceService $balanceService
     */
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Get the current balance for the authenticated user
     * 
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        try {
            // Get current user's balance using the service layer
            $balance = $this->balanceService->getCurrentBalance(
                Auth::user()->id
            );

            // Return successful response with balance
            return response()->json([
                'status' => 'success',
                'data' => [
                    'balance' => $balance,
                    'currency' => 'USD', // Can be made configurable
                    'timestamp' => now()
                ]
            ]);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Balance retrieval failed: ' . $e->getMessage());

            // Return appropriate error response
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed balance information including pending transactions
     * 
     * @return JsonResponse
     */
    public function details(): JsonResponse
    {
        try {
            // Get detailed balance information
            $details = $this->balanceService->getBalanceDetails(
                Auth::user()->id
            );

            return response()->json([
                'status' => 'success',
                'data' => $details
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve balance details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
