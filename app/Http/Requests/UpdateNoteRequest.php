<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateNoteRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        Log::info('ajung in UpdateNoteRequest');
        return [
            'title' => 'string|max:100|required',
            'content' => 'string|required',
            'is_completed' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'metadata' => ['sometimes', 'array'],
            'metadata.items' => ['nullable', 'array'],
            'metadata.items.*.text' => ['required_with:metadata.items', 'string'],
            'metadata.items.*.completed' => ['required_with:metadata.items', 'boolean'],
        ];
    }
}
