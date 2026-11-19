<div class="p-4 mt-4 py-3 card shadow-lg moveAbove-unique bg-white-custom lightborder-unique">
    <h5 class="mb-4">Ticket Rating</h5>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead style="background-color:#0A3AA9;color:#fff;padding:0 20px">
                <tr class="bordered-row" style="font-size:0.65rem">
                    <th style="padding:10px">#</th>
                    <th style="padding:10px"> Ticket Type</th>
                    <th style="padding:10px"> Ticket SubType</th>
                    <th style="padding:10px"> Requested By</th>
                    <th style="padding:10px">Assign To</th>
                    <th style="padding:10px">Happiness Rating</th>

                </tr>
            </thead>
            <tbody>
                @foreach ($lastTicketsClosed as $ticket)
                    <tr style="font-size:0.65rem">
                        <td style="padding:10px">{{ $loop->iteration }}</td>
                        <td style="padding:10px">{{ $ticket->ticketType->name }}</td>
                        <td style="padding:10px">{{ $ticket->ticketsubtype->name }}</td>
                        <td style="padding:10px"> {{ $ticket->requestedBy->name ?? 'N/A' }} </td>
                        <td style="padding:10px"> {{ $ticket->assignto->name ?? 'N/A' }} </td>
                        <td style="padding:10px">
                            <!-- Display Rating as Stars -->
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <=  $ticket->feedback->first()->rate )
                                    <span class="fa fa-star"></span>
                                @else
                                    <span class="fa fa-star-o"></span> <!-- Empty stars if needed -->
                                @endif
                            @endfor

                            <!-- Add emoji based on the rating -->
                            @if ($ticket->feedback->first()->rate == 5)
                                <span>😁</span> <!-- Emoji for 5 stars -->
                            @elseif ($ticket->feedback->first()->rate >= 4)
                                <span>😊</span> <!-- Emoji for 4 stars -->
                            @elseif ($ticket->feedback->first()->rate >= 3)
                                <span>🙂</span> <!-- Emoji for 3 stars -->
                            @elseif ($ticket->feedback->first()->rate >= 2)
                                <span>😐</span> <!-- Emoji for 2 stars -->
                            @else
                                <span>😡</span> <!-- Emoji for 1 star or below -->
                            @endif


                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


