<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class IndexAuthorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'sort_field' => $this->input('sort_field') ?? 'id',
            'sort_order' => $this->input('sort_order') ?? 'asc',
            'per_page' => $this->input('per_page') ?? 20,
            'search' => $this->input('search') ?? '',
            'fields' => $this->input('fields') ?? '*',
        ]);

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort_field' => 'in:id,created_at,name',
            'sort_order' => 'in:asc,desc',
            'per_page' => 'integer|min:1',
            'search' => 'string',
            'fields' => 'string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
            'status' => 422
        ], 422));
    }
}