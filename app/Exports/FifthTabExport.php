<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class FifthTabExport implements FromArray
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
        $data[] = ['Sr #', 'Ticket Type', 'Ticket Subtype', 'Rating'];
        ;

        // Add data rows for the first tab
        $count = 0;
        foreach ($this->data as $ticket) {
            $data[] = [
                $count,
                $ticket->tickettype->name,
                $ticket->ticketsubtype->name,
                $ticket->rate,
               
            ];
            $count++;
        }


        return $data;
    }
}
