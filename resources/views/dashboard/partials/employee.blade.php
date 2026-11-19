<div class=" my-2">
    <div class="row">
        <!-- Helpdesk Insights -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card ticket-card shadow-lg lightborder-unique">
                <div class="px-4 py-2">
                    <div class="d-flex align-items-center">
                        <div class="icon-placeholder">
                            <img src="{{ asset('assets/img/ticket_employee_svg.svg') }}" alt="icons"
                                style="width:100%;height:100%;">

                        </div>
                        <div>
                            <h3 class="mb-0 text-black" style="font-weight:20px">{{ $total_tickets }}</h3>

                            <p class="text text-black" style="margin:0;font-weight:16px">Total Helpdesk Tickets</p>
                        </div>
                    </div>
                    <div class="mt-4 tickets-status-parent">
                        <p class="ticket-status "><span class="ticket-status-tab ticket-status-urgent">Urgent</span>
                            <span class="float-end " style="color:#000">{{ $priorityData['Urgent'] }}</span>
                        </p>
                        <p class="ticket-status "> <span class="ticket-status-tab ticket-status-repeated"> High</span>
                            <span class="float-end " style="color:#000">{{ $priorityData['High'] }}</span>
                        </p>
                        <p class="ticket-status "> <span class="ticket-status-tab ticket-status-new"> Medium</span>
                            <span class="float-end " style="color:#000">{{ $priorityData['Medium'] }}</span>
                        </p>
                        <p class="ticket-status "> <span class="ticket-status-tab ticket-status-closed"> Low</span>
                            <span class="float-end " style="color:#000">{{ $priorityData['Low'] }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Tickets -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="card ticket-card shadow-lg lightborder-unique">
                <div class="py-2 px-4">
                    <p style="font-size:20px;color:#000;font-weight:600">My Tickets</p>

                    <div class="list-group-unique mt-3">
                        @if($tickets->isEmpty())
                            <!-- No tickets message -->
                            <div class="d-flex justify-content-center mt-5">
                                <p class="heading">No data found</p>
                            </div>
                        @else
                            <!-- Dynamically render each ticket -->
                            @foreach($tickets as $ticket)
                                <a href="{{route('ticket.view', $ticket->id)}}" class="text-decoration-none ">
                                    <div class="mt-3 {{ in_array($loop->index, [0, 2, 4]) ? 'bg-changed-unique' : '' }} ticket-card-1"
                                        style="cursor: pointer;">
                                        <p class="heading">
                                            {{ $ticket->description ? Str::limit($ticket->description, 80) : 'No description is provided' }}
                                        </p>

                                        <p class="text mb-0">{{ $ticket->created_at->diffForHumans() }}</p>

                                    </div>
                                </a>
                            @endforeach

                            <!-- Pagination links -->
                        @endif
                    </div>

                </div>
                <div class="px-4 mb-2 position-absolute d-flex justify-content-between align-items-center bottom-0"
                    style="width:95%">
                    <div class="">
                        Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} 
                    </div>
                    <div class="">
                        @if ($tickets->lastPage() > 1)
                            <nav>
                                <ul class="pagination justify-content-center">
                                    {{-- Previous Page Link --}}
                                    @if ($tickets->currentPage() > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $tickets->previousPageUrl() }}">{{$tickets->currentPage() - 1}}</a>
                                        </li>
                                    @endif

                                    {{-- Current Page --}}
                                    <li class="page-item active">
                                        <span class="page-link">{{ $tickets->currentPage() }}</span>
                                    </li>

                                    {{-- Next Page Link --}}
                                    @if ($tickets->currentPage() < $tickets->lastPage())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $tickets->nextPageUrl() }}">{{$tickets->currentPage() + 1}}</a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>


            </div>

        </div>

    </div>
</div>