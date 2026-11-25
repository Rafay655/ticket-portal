<style>
.dashboard-donuts { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
.dashboard-donut-card { min-width:0; padding:12px 16px 18px; }
.dashboard-donut-card h5 { text-align:center; margin:0 0 14px; }
.dashboard-donut-canvas { position:relative; height:250px; width:100%; }
.dashboard-donut-legend { display:flex; flex-wrap:wrap; justify-content:center; gap:8px 14px; margin-top:16px; }
.dashboard-donut-legend .legend-item { display:flex; align-items:center; gap:6px; min-width:0; max-width:100%; font-size:12px; }
.dashboard-donut-legend .legend-color { display:inline-block; width:10px; height:10px; flex-shrink:0; }
.dashboard-donut-legend .legend-label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:170px; }
@media(max-width:991.98px) { .dashboard-donuts { grid-template-columns:1fr; } }
</style>
<div class="dashboard-donuts">
    @foreach([
        ['title' => 'Ticket Type', 'canvas' => 'myChart', 'rows' => $ticketTypePercentages, 'relation' => 'tickettype'],
        ['title' => 'Ticket Category', 'canvas' => 'ticketCategoryChart', 'rows' => $ticketCategoryPercentages, 'relation' => null],
        ['title' => 'Ticket Sub-Category', 'canvas' => 'myDoughnutChart', 'rows' => $ticketSubTypePercentages, 'relation' => 'ticketsubtype'],
    ] as $chart)
        <div class="card dashboard-donut-card lightborder-unique shadow-lg">
            <h5>{{ $chart['title'] }}</h5>
            <div class="dashboard-donut-canvas">
                <canvas id="{{ $chart['canvas'] }}" role="img" aria-label="{{ $chart['title'] }} ticket counts"></canvas>
            </div>
            <div class="dashboard-donut-legend">
                @forelse($chart['rows'] as $ticket)
                    @php $label = $chart['relation'] ? ($ticket->{$chart['relation']}->name ?? 'Not assigned') : $ticket->name; @endphp
                    <div class="legend-item">
                        <span class="legend-color" style="background-color:{{ $ticket->color }};"></span>
                        <span class="legend-label" title="{{ $label }}">{{ $label }}</span>
                    </div>
                @empty
                    <span class="text-sm text-muted">No tickets available</span>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
