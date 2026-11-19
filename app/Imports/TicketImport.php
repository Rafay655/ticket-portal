<?php

namespace App\Imports;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\TicketSubType;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class TicketImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    public $failures = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Fetching the ticket type and subtype IDs
        $ticketType = TicketType::where('name', $row['ticket_type'])->first();
        $ticketTypeId = $ticketType ? $ticketType->id : null;

        $ticketSubType = TicketSubType::where('name', $row['ticket_sub_type'])->first();
        $ticketSubTypeId = $ticketSubType ? $ticketSubType->id : null;


        // Creating a new ticket model instance
        return new Ticket([
            'tickettype_id' => $ticketTypeId,
            'ticketsubtype_id' => $ticketSubTypeId,
            'requested_by' => $row['requested_by'],
            'assigned_to' => $row['assigned_to'],
            'status' => $row['status'],
            'created_at' => now(),
            'updated_at' => now(),
            'escalation_time_start' => now(),
            'current_level' => 1
        ]);
    }

    /**
     * Define validation rules for the imported data.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.ticket_type' => 'required|exists:ticket_types,name',
            '*.ticket_sub_type' => 'required|exists:ticket_sub_types,name',
            '*.requested_by' => 'required|exists:users,id',
            '*.assigned_to' => 'required|exists:users,id',
            // '*.assigned_to_user_id' => 'nullable|exists:users,id',
            '*.status' => 'required|string',
           
        ];
    }

    /**
     * Collect the failures and store them for returning to the front-end.
     *
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        // Collect all failures
        $this->failures = $failures;

        // Optionally, you can log failures for debugging purposes
        Log::error('Import failures:', ['failures' => $this->failures]);

        // You can return failures back to the session to display in the view
        session()->flash('failures', $this->failures);
    }
}
