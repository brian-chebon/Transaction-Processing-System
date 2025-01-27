<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class AccountNotFoundException extends Exception
{
    /**
     * @var int|string|null
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $identifierType;

    /**
     * Create a new account not found exception.
     *
     * @param int|string|null $identifier
     * @param string $identifierType
     * @param string|null $message
     */
    public function __construct(
        $identifier = null,
        string $identifierType = 'id',
        ?string $message = null
    ) {
        $this->identifier = $identifier;
        $this->identifierType = $identifierType;

        $message = $message ?? $this->buildMessage();

        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Build the error message.
     *
     * @return string
     */
    protected function buildMessage(): string
    {
        if ($this->identifier === null) {
            return 'Account not found';
        }

        return sprintf(
            'Account with %s "%s" not found',
            $this->identifierType,
            $this->identifier
        );
    }

    /**
     * Get the identifier that was used to look up the account.
     *
     * @return int|string|null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the type of identifier that was used.
     *
     * @return string
     */
    public function getIdentifierType(): string
    {
        return $this->identifierType;
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
                'identifier' => $this->identifier,
                'identifier_type' => $this->identifierType,
                'code' => 'ACCOUNT_NOT_FOUND'
            ]
        ];
    }

    /**
     * Create an exception for an account not found by ID.
     *
     * @param int $id
     * @return static
     */
    public static function withId(int $id): self
    {
        return new static($id, 'id');
    }

    /**
     * Create an exception for an account not found by user ID.
     *
     * @param int $userId
     * @return static
     */
    public static function withUserId(int $userId): self
    {
        return new static($userId, 'user_id');
    }

    /**
     * Create an exception for an account not found by reference.
     *
     * @param string $reference
     * @return static
     */
    public static function withReference(string $reference): self
    {
        return new static($reference, 'reference');
    }
}
