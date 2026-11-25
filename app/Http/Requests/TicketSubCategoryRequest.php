<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('ticketsubtype') ? 'ticketSubType.edit' : 'ticketSubType.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) $this->merge(['name' => trim($this->input('name'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_category_id' => ['required', 'integer', Rule::exists('ticket_categories', 'id')->where('ticket_type_id', $this->input('ticket_type_id'))],
            'priority' => ['required', 'in:Urgent,High,Medium,Low'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $subCategory = $this->route('ticketsubtype');
            if ($subCategory && $subCategory->ticket_category_id && (int) $subCategory->ticket_category_id !== (int) $this->input('ticket_category_id') && $subCategory->tickets()->exists()) {
                $validator->errors()->add('ticket_category_id', 'This Ticket Sub-Category is used by existing tickets. Its Ticket Category cannot be changed.');
            }
        });
    }
}
