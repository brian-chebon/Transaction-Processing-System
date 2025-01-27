<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InsufficientFundsException extends Exception
{
    /**
     * @var float
     */
    protected $available;

    /**
     * @var float
     */
    protected $requested;

    /**
     * Create a new insufficient funds exception.
     *
     * @param float $available
     * @param float $requested
     * @param string|null $message
     */
    public function __construct(
        float $available,
        float $requested,
        ?string $message = null
    ) {
        $this->available = $available;
        $this->requested = $requested;

        $message = $message ?? sprintf(
            'Insufficient funds. Available balance: %.2f, Requested amount: %.2f',
            $available,
            $requested
        );

        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Get the available balance.
     *
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return $this->available;
    }

    /**
     * Get the requested amount.
     *
     * @return float
     */
    public function getRequestedAmount(): float
    {
        return $this->requested;
    }

    /**
     * Get the shortage amount.
     *
     * @return float
     */
    public function getShortage(): float
    {
        return $this->requested - $this->available;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @return array
     */
    public function render(): array
    {
        return [
            'status' => 'error',
            'message' => $this->getMessage(),
            'data' => [
                'available_balance' => $this->available,
                'requested_amount' => $this->requested,
                'shortage' => $this->getShortage(),
                'code' => 'INSUFFICIENT_FUNDS'
            ]
        ];
    }
}
