<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ThirdTabExport implements FromArray
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
        $data[] = ['Sr #', 'Ticket Type', 'Ticket Subtype', 'Status'];
        ;

        // Add data rows for the first tab
        $count = 1;
        foreach ($this->data as $ticket) {
            $data[] = [
                $count,
                $ticket->tickettype->name,
                $ticket->ticketsubtype->name,
                $ticket->status,
               
            ];
            $count++;
        }


        return $data;
    }
}
