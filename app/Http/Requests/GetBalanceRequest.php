<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users to check balance
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'include_pending' => [
                'sometimes',
                'boolean'
            ],
            'date_from' => [
                'sometimes',
                'date',
                'before_or_equal:today'
            ],
            'date_to' => [
                'sometimes',
                'date',
                'after_or_equal:date_from',
                'before_or_equal:today'
            ],
            'currency' => [
                'sometimes',
                'string',
                'size:3'  // ISO 4217 currency codes are 3 characters
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
            'date_from.before_or_equal' => 'Start date cannot be in the future',
            'date_to.before_or_equal' => 'End date cannot be in the future',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'currency.size' => 'Currency must be a valid 3-letter ISO code'
        ];
    }

    /**
     * Prepare the data for validation
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Convert string boolean to actual boolean if present
        if ($this->has('include_pending')) {
            $this->merge([
                'include_pending' => filter_var($this->include_pending, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Convert currency to uppercase if present
        if ($this->has('currency')) {
            $this->merge([
                'currency' => strtoupper($this->currency)
            ]);
        }
    }

    /**
     * Get query parameters as an array
     * 
     * @return array
     */
    public function getQueryParams(): array
    {
        return array_filter([
            'include_pending' => $this->include_pending,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'currency' => $this->currency ?? 'USD'
        ]);
    }
}
