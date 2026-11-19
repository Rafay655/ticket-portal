<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class FirstTabExport implements FromArray
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $data = [];
        // Define the headers for the first sheet
        $data[] = ['ID', 'Ticket Type', 'Ticket Subtype', 'Requested By', 'Status','Assigned To','Level','Description'];;

        // Add data rows for the first tab
        foreach ($this->data as $ticket) {
            $data[] = [
                $ticket->id,
                $ticket->tickettype->name,
                $ticket->ticketsubtype->name,
                $ticket->requestedby->name,
                $ticket->status,
                $ticket->assignto->name ,
                $ticket->current_level ,
                $ticket->description ,
            ];
        }

        return $data;
    }
}
