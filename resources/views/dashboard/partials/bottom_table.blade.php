<section class="card dashboard-panel">
    <h5>Ticket Rating</h5>
    <div class="table-responsive">
        <table class="table dashboard-table mb-0" id="dashboard-ticket-ratings">
            <thead><tr>
                <th>Sr#</th><th>Ticket Type</th><th>Ticket Category</th><th>Ticket Sub-Category</th>
                <th>Requested By</th><th>Assigned To</th><th>Happiness Rating</th>
            </tr></thead>
            <tbody>
                @forelse($lastTicketsClosed as $ticket)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $ticket->ticketType->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->ticket_category_name ?? 'Not assigned' }}</td>
                        <td>{{ $ticket->ticketsubtype->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->requestedBy->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->assignto->name ?? 'Not assigned' }}</td>
                        <td>
                            @php $rating = $ticket->feedback->first()?->rate; @endphp
                            @if($rating !== null)
                                <span class="dashboard-rating" aria-label="{{ $rating }} out of 5 stars">
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="fa {{ $star <= $rating ? 'fa-star' : 'fa-star-o' }}" aria-hidden="true"></i>
                                    @endfor
                                </span>
                                <span class="text-muted ms-1">{{ $rating }}/5</span>
                            @else
                                <span class="text-muted">Not rated yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dashboard-empty">No ticket ratings available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
