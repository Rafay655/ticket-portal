<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('ticketcategory') ? 'ticketCategory.edit' : 'ticketCategory.create';
        return $this->user()?->can($permission) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        if (is_string($this->input('name'))) $values['name'] = trim($this->input('name'));
        if (is_string($this->input('code'))) $values['code'] = strtoupper(trim($this->input('code')));
        $this->merge($values);
    }

    public function rules(): array
    {
        $category = $this->route('ticketcategory');
        return [
            'name' => ['required', 'string', 'max:255'],
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('ticket_categories', 'code')->ignore($category?->id)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('ticketcategory');
            if ($category && $category->ticket_type_id && (int) $category->ticket_type_id !== (int) $this->input('ticket_type_id')
                && ($category->subCategories()->exists() || $category->tickets()->exists())) {
                $validator->errors()->add('ticket_type_id', 'This Ticket Category is in use. Its Ticket Type cannot be changed.');
            }
        });
    }

    public function messages(): array
    {
        return ['code.unique' => 'This category code is already in use. Please enter a different code.'];
    }
}
