<?php

namespace App\Services;

use App\Models\TicketCategory;
use App\Models\TicketSubType;
use Illuminate\Validation\ValidationException;

class TicketHierarchy
{
    public function resolve(int $typeId, $categoryId, $subCategoryId): array
    {
        $subCategory = TicketSubType::with('category')->find($subCategoryId);
        if (!$subCategory) {
            throw ValidationException::withMessages(['ticketsubtype_id' => 'Please select a valid Ticket Sub-Category.']);
        }
        $category = $subCategory->category;
        if (!$category || !$category->ticket_type_id) {
            throw ValidationException::withMessages(['ticketsubtype_id' => 'This Ticket Sub-Category has not been mapped to a Ticket Category and Ticket Type. Please contact your administrator.']);
        }
        if ((int) $category->ticket_type_id !== $typeId) {
            throw ValidationException::withMessages(['ticketsubtype_id' => 'The selected Ticket Sub-Category does not belong to this Ticket Type.']);
        }
        // Older mobile clients may omit the category; derive it from their mapped sub-category.
        if ($categoryId !== null && $categoryId !== '' && (int) $categoryId !== (int) $category->id) {
            throw ValidationException::withMessages(['ticket_category_id' => 'The selected Ticket Sub-Category does not belong to this Ticket Category.']);
        }
        return [
            'tickettype_id' => $typeId,
            'ticket_category_id' => $category->id,
            'ticketsubtype_id' => $subCategory->id,
        ];
    }

    public function options(): array
    {
        return [
            'categories' => TicketCategory::whereNotNull('ticket_type_id')->orderBy('name')->get(['id', 'name', 'code', 'ticket_type_id'])->toArray(),
            'subCategories' => TicketSubType::whereHas('category', fn ($query) => $query->whereNotNull('ticket_type_id'))
                ->orderBy('name')->get(['id', 'name', 'priority', 'ticket_category_id'])->toArray(),
        ];
    }
}
