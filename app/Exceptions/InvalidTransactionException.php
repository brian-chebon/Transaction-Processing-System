<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InvalidTransactionException extends Exception
{
    /**
     * @var string
     */
    protected $errorCode;

    /**
     * @var array
     */
    protected $context;

    /**
     * Error codes and their messages
     */
    const ERROR_CODES = [
        'INVALID_AMOUNT' => 'Transaction amount must be greater than zero',
        'INVALID_TYPE' => 'Invalid transaction type',
        'DUPLICATE_REFERENCE' => 'Transaction with this reference already exists',
        'INACTIVE_ACCOUNT' => 'Account is not active',
        'SUSPENDED_ACCOUNT' => 'Account is suspended',
        'LOCKED_ACCOUNT' => 'Account is temporarily locked',
        'EXPIRED_TRANSACTION' => 'Transaction has expired',
        'REVERSED_TRANSACTION' => 'Transaction has already been reversed',
        'INVALID_STATUS_TRANSITION' => 'Invalid transaction status transition'
    ];

    /**
     * Create a new invalid transaction exception.
     *
     * @param string $errorCode
     * @param array $context
     * @param string|null $message
     */
    public function __construct(
        string $errorCode,
        array $context = [],
        ?string $message = null
    ) {
        $this->errorCode = $errorCode;
        $this->context = $context;

        $message = $message ?? (self::ERROR_CODES[$errorCode] ?? 'Invalid transaction');

        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Get the error code.
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the error context.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Check if error is of a specific code.
     *
     * @param string $code
     * @return bool
     */
    public function isError(string $code): bool
    {
        return $this->errorCode === $code;
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
                'error_code' => $this->errorCode,
                'context' => $this->context,
                'details' => self::ERROR_CODES[$this->errorCode] ?? null
            ]
        ];
    }

    /**
     * Create an exception for an invalid amount.
     *
     * @param float $amount
     * @return static
     */
    public static function invalidAmount(float $amount): self
    {
        return new static('INVALID_AMOUNT', ['amount' => $amount]);
    }

    /**
     * Create an exception for an invalid type.
     *
     * @param string $type
     * @return static
     */
    public static function invalidType(string $type): self
    {
        return new static('INVALID_TYPE', ['type' => $type]);
    }

    /**
     * Create an exception for a duplicate reference.
     *
     * @param string $reference
     * @return static
     */
    public static function duplicateReference(string $reference): self
    {
        return new static('DUPLICATE_REFERENCE', ['reference' => $reference]);
    }

    /**
     * Create an exception for an inactive account.
     *
     * @param int $accountId
     * @return static
     */
    public static function inactiveAccount(int $accountId): self
    {
        return new static('INACTIVE_ACCOUNT', ['account_id' => $accountId]);
    }
}
