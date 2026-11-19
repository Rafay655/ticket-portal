<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SecondTabExport implements FromArray
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
        $data[] = ['Sr #', 'Ticket Type', 'Ticket Subtype', 'Open', 'Closed', 'In Progress', 'Feedback'];
        ;
        $count = 1;
        // Add data rows for the first tab
        foreach ($this->data as $ticket) {
            $data[] = [
                $count,
                $ticket->tickettype->name,
                $ticket->ticketsubtype->name,
                $ticket->open_count,
                $ticket->closed_count,
                $ticket->inprogress_count,
                $ticket->feedback_count,
            ];
            $count++;
        }

        return $data;
    }
}
