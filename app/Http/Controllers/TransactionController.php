<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Exception;

class TransactionController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $transactionService;

    /**
     * Constructor to inject dependencies
     * 
     * @param TransactionService $transactionService
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Create a new transaction
     * 
     * @param CreateTransactionRequest $request Validated request object
     * @return JsonResponse
     */
    public function store(CreateTransactionRequest $request): JsonResponse
    {
        try {
            // Start database transaction to ensure atomicity
            DB::beginTransaction();

            // Process the transaction using the service layer
            $transaction = $this->transactionService->createTransaction(
                $request->user()->id,
                $request->amount,
                $request->type
            );

            // If everything is successful, commit the transaction
            DB::commit();

            // Return successful response with transaction details
            return response()->json([
                'status' => 'success',
                'message' => 'Transaction processed successfully',
                'data' => $transaction
            ], 201);
        } catch (Exception $e) {
            // If any error occurs, rollback the transaction
            DB::rollBack();

            // Log the error for debugging
            Log::error('Transaction failed: ' . $e->getMessage());

            // Return appropriate error response
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction history for the authenticated user
     * 
     * @return JsonResponse
     */
    public function history(): JsonResponse
    {
        try {
            $transactions = $this->transactionService->getUserTransactions(
                Auth::user()->id
            );

            return response()->json([
                'status' => 'success',
                'data' => $transactions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve transaction history',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
