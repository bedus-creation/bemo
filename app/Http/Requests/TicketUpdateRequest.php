<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:open,pending,closed'],
            'category' => [
                'sometimes',
                'nullable',
                Rule::exists('ticket_categories', 'id'),
            ],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
