<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Check if user is authenticated
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'gt:0',                // Amount must be greater than 0
                'max:999999999.99'     // Prevent unreasonably large transactions
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['credit', 'debit'])  // Only allow credit or debit transactions
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
            ],
            'reference' => [
                'nullable',
                'string',
                'max:50',
                'unique:transactions,reference'  // Prevent duplicate transactions
            ]
        ];
    }

    /**
     * Get custom messages for validator errors
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Transaction amount is required',
            'amount.numeric' => 'Transaction amount must be a number',
            'amount.gt' => 'Transaction amount must be greater than zero',
            'amount.max' => 'Transaction amount exceeds maximum allowed value',
            'type.required' => 'Transaction type is required',
            'type.in' => 'Transaction type must be either credit or debit',
            'reference.unique' => 'This transaction has already been processed'
        ];
    }

    /**
     * Prepare the data for validation
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Generate a unique reference if none provided
        if (!$this->has('reference')) {
            $this->merge([
                'reference' => uniqid('TXN_', true)
            ]);
        }

        // Clean and format amount to ensure proper decimal handling
        if ($this->has('amount')) {
            $this->merge([
                'amount' => number_format((float) $this->amount, 2, '.', '')
            ]);
        }
    }
}
