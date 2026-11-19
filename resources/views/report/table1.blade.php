<table border="1" cellspacing="0" cellpadding="5"  style="width: 100%; border-collapse: collapse;">
    <tr>
        <th>Sr #</th>
        <th>Ticket Type</th>
        <th>Ticket Subtype</th>
        <th>Open</th>
        <th>Closed</th>
        <th>In progress</th>
        <th>Feedback</th>
    </tr>
    @foreach ($ticketData as $ticket)
    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $ticket->tickettype->name }}</td>
        <td>{{ $ticket->ticketsubtype->name }}</td>
        <td>{{ $ticket->open_count }}</td>
        <td>{{ $ticket->closed_count}}</td>
        <td>{{ $ticket->inprogress_count }}</td>
        <td>{{ $ticket->feedback_count }}</td>
    </tr>
    @endforeach
</table>
