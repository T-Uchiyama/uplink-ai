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
            'member_id' => ['required', 'exists:members,id'],
            'goal' => ['required', 'string', 'max:255'],
            'available_hours' => ['required', 'integer', 'min:1'],
            'previous_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }
}
