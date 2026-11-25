<table border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse;">
    <tr>
        <th style="text-align: left;">Sr#</th>
        <th style="text-align: left;">Ticket Type</th>
        <th>Ticket Category</th>
        <th style="text-align: left;">Ticket Sub-Category</th>
        <th style="text-align: left;">Status</th>
    </tr>
    @foreach ($ticketData as $ticket)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $ticket->tickettype->name }}</td>
        <td>{{ $ticket->ticket_category_name ?? 'Not assigned' }}</td>
        <td>{{ $ticket->ticketsubtype->name }}</td>
        <td>{{ $ticket->status }}</td>
    </tr>
    @endforeach
</table>
