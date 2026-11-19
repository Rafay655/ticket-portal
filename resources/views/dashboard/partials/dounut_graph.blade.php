<div class="mt-4">
    <div class="d-flex bg-white-custom flex-column flex-md-row justify-content-between">
        <div class="d-flex card justify-content-center moveAbove-unique chart-box-unique mb-5 mb-md-0 lightborder-unique shadow-lg">
            <h5>Ticket Type</h5>
            <div class="flex-grow mx-auto p-4 text-center" style="width: 100%;flex:1">
                <div class="chartBox position-relative">
                    <canvas id="myChart" height="250px"></canvas>
                </div>
                    <div class="legend-container1">
                        @foreach ($ticketTypePercentages as $ticket)
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: {{ $ticket->color }};"></span>
                                <span class="legend-label" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $ticket->tickettype->name }}">{{ $ticket->tickettype->name }}</span>
                            </div>
                        @endforeach
                    </div>
            </div>
        </div>


        <!-- Second box (Articles by Categories) -->
        <div class="d-flex card align-items-between moveAbove-unique chart-box-unique lightborder-unique shadow-lg">
            <h5>Ticket SubType</h5>
            <div class="flex-grow">
                <div class=" justify-content-center mt-4" style="width: 100%;flex:1">
                    <div class="text-center ">
                        <canvas id="myDoughnutChart" height="250px"></canvas>
                    </div>
                    <div class="legend-container container">
                    @foreach ($ticketSubTypePercentages as $ticket)
                        <div class="legend-item ">
                            <span class="legend-color" style="background-color: {{ $ticket->color }};"></span>
                            <span class="legend-label" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $ticket->ticketsubtype->name }}">{{ \Illuminate\Support\Str::limit($ticket->ticketsubtype->name, 7, '...') }}</span>
                        </div>
                    @endforeach
                </div>
                </div>
            </div>
        </div>
    </div>
</div>