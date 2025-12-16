<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchTranslationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'locale' => 'sometimes|string|max:10',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:100',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }
}
