<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal' => ['required', 'string', 'max:1000'],
            'available_hours' => ['required', 'numeric', 'min:0.5', 'max:200'],
            'previous_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
