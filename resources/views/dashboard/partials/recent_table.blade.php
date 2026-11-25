<section class="card dashboard-panel">
    <h5>Recent Tickets</h5>
    <div class="table-responsive">
        <table class="table dashboard-table mb-0" id="dashboard-recent-tickets">
            <thead><tr>
                <th>Sr#</th><th>Ticket Type</th><th>Ticket Category</th><th>Ticket Sub-Category</th>
                <th>Requested By</th><th>Assigned To</th><th>Status</th><th>Priority</th>
            </tr></thead>
            <tbody>
                @forelse($lastTickets as $ticket)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $ticket->ticketType->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->ticket_category_name ?? 'Not assigned' }}</td>
                        <td>{{ $ticket->ticketsubtype->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->requestedBy->name ?? 'N/A' }}</td>
                        <td>{{ $ticket->assignto->name ?? 'Not assigned' }}</td>
                        <td>
                            @php
                                $statusKey = strtolower(str_replace([' ', '_', '-'], '', $ticket->status ?? ''));
                                $statuses = ['open' => ['Open', 'open'], 'inprogress' => ['In Progress', 'progress'], 'feedback' => ['Feedback', 'feedback'], 'closed' => ['Closed', 'closed']];
                                [$statusLabel, $statusClass] = $statuses[$statusKey] ?? [$ticket->status ?? 'Unknown', 'closed'];
                            @endphp
                            <span class="dashboard-status dashboard-status-{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $ticket->ticketsubtype->priority ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="dashboard-empty">No recent tickets available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
