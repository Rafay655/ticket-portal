<div class="p-4 mt-4 py-3 shadow-lg bg-white-custom moveAbove-unique lightborder-unique card">
    <h5 class="mb-4">Recent Tickets</h5>
    <div class="table-responsive">

        <table class="table table-striped table-hover ">
            <thead style="background-color:#0A3AA9;color:#fff;padding:0 20px">
                <tr class="bordered-row" style="font-size:0.65rem">
                    <th style="padding:10px ">#</th>
                    <th style="padding:10px ">Ticket Type</th>
                    <th style="padding:10px ">Ticket SubType</th>
                    <th style="padding:10px ">Requested By</th>
                    <th style="padding:10px ">Assign To</th>

                    <th style="padding:10px;text-align:center ">Status</th>
                    <th style="padding:10px ">Priority</th>
                </tr>

            </thead>
            <tbody>


                @foreach ($lastTickets as $ticket)
                    <tr style="font-size:0.65rem">
                        <td style="padding:10px ; text-align:justify;">{{ $loop->iteration }} </td>
                        <td style="padding:10px ; text-align:justify;">
                            {{ $ticket->ticketType->name ?? 'N/A' }}
                        </td>
                       
                        <td style="padding:10px ; text-align:justify;">
                            {{ $ticket->ticketsubtype->name ?? 'N/A' }}
                        </td>

                        <td style="padding:10px ; text-align:justify;">
                            {{ $ticket->requestedBy->name ?? 'N/A' }} 
                        </td>

                        <td style="padding:10px ; text-align:justify;">
                            {{ $ticket->assignto->name ?? 'N/A' }} 
                        </td>
                        <td style="padding:10px 20px;text-align:center">
                            @if ($ticket['status'] == 'Open')
                                <span class="status-open padding-custom recent-status-btn-unique">Open</span>
                            @elseif ($ticket['status'] == 'in_progress')
                                <span class="status-inprogress padding-custom recent-status-btn-unique">In
                                    Progress</span>
                            @elseif ($ticket['status'] == 'feedback')
                                <span class="status-feedback padding-custom recent-status-btn-unique">Feedback</span>
                            @else
                                <span class="status-closed padding-custom recent-status-btn-unique">Closed</span>
                            @endif
                        </td>
                        <td style="padding:10px ; text-align:justify;">{{ $ticket->ticketsubtype->priority }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>