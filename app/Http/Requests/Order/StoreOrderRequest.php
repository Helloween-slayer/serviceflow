<?php

namespace App\Http\Requests\Order;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role_id === 3;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'nullable|numeric',
        'deadline' => 'nullable|date',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
        'photos.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp',
        'files.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,png,zip',
    ];
    }
}
