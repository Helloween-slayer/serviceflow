<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1|max:100000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Мінімальна сума поповнення — 1 грн',
            'amount.max' => 'Максимальна сума поповнення — 100 000 грн',
        ];
    }

}
