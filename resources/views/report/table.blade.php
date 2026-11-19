<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Ticket Type</th>
        <th>Ticket Subtype</th>
        <th>Requested By</th>
        <th>Status</th>
        <th>Assigned To</th>
        <th>Level</th>
        <th>Description</th>
    </tr>
    @foreach ($ticketData as $ticket)
        <tr>
            <td>{{ $ticket->id }}</td>
            <td>{{ $ticket->tickettype->name }}</td>
            <td>{{ $ticket->ticketsubtype->name }}</td>
            <td>{{ $ticket->requestedby->name }}</td>
            <td>{{ $ticket->status }}</td>
            <td>{{ $ticket->assignto->name }}</td>
            <td>{{ $ticket->current_level }}</td>
            <td>{{ $ticket->description }}</td>
        </tr>
    @endforeach
</table>