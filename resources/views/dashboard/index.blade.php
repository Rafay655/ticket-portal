@extends('layouts.main')


@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm">
            <a class="text-white" href="javascript:;">
                <i class="ni ni-box-2"></i>
            </a>
        </li>
        <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white"
                href="javascript:;">Dashboard</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Dashboard</h6>
</nav>
@endsection
@section('style')
<link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}?v=20261125-2">
@endsection
@section('content')
<div class="dashboard-layout">
@include('dashboard.partials.top_stats')
@php
    $allowedDesignations = [25];
@endphp

@if (
    auth()->user()->can('admindashboard.index') ||
    in_array(auth()->user()->designation_id, $allowedDesignations)
)
    @include('dashboard.partials.line_graph')
    @include('dashboard.partials.recent_table')
    @include('dashboard.partials.dounut_graph')
    @include('dashboard.partials.bottom_table')
@else
    @include('dashboard.partials.employee')
@endif

</div>
@endsection

@section('script')
@if (
    auth()->user()->can('admindashboard.index') ||
    in_array(auth()->user()->designation_id, $allowedDesignations)
)
<script>

const ticketData = @json($ticketTypePercentages);
const ticketCategoryData = @json($ticketCategoryPercentages);
const ticketSubtypeData = @json($ticketSubTypePercentages);

const pieLabelsLine = {
    id: 'pieLabelsLine',
    afterDraw(chart) {
        const {ctx, chartArea: {left, right, top, bottom}} = chart;
        const cx = (left + right) / 2;
        const cy = (top + bottom) / 2;
        ctx.save();
        chart.getDatasetMeta(0).data.forEach((arc, index) => {
            const count = chart.data.datasets[0].data[index];
            if (!count || !arc.outerRadius) return;
            const angle = (arc.startAngle + arc.endAngle) / 2;
            const x = cx + Math.cos(angle) * arc.outerRadius;
            const y = cy + Math.sin(angle) * arc.outerRadius;
            const side = Math.cos(angle) >= 0 ? 1 : -1;
            const endX = x + side * 12;
            const endY = y + Math.sin(angle) * 12;
            ctx.strokeStyle = chart.data.datasets[0].backgroundColor[index];
            ctx.fillStyle = ctx.strokeStyle;
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(x,y); ctx.lineTo(endX,endY); ctx.lineTo(endX + side * 8,endY); ctx.stroke();
            ctx.font = '11px Arial'; ctx.textAlign = side > 0 ? 'left' : 'right'; ctx.textBaseline = 'middle';
            ctx.fillText(count, endX + side * 12, endY);
        });
        ctx.restore();
    }
};
function createTicketDonut(id, rows, relation) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: rows.map(item => relation ? (item[relation]?.name ?? 'Not assigned') : item.name),
            datasets: [{data: rows.map(item => Number(item.counter)), backgroundColor: rows.map(item => item.color), borderWidth: 0}]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '70%',
            layout: {padding: 32},
            plugins: {legend: {display: false}}
        },
        plugins: [pieLabelsLine]
    });
}
createTicketDonut('myChart', ticketData, 'tickettype');
createTicketDonut('ticketCategoryChart', ticketCategoryData, null);
createTicketDonut('myDoughnutChart', ticketSubtypeData, 'ticketsubtype');

    // Initialize line chart
    const ctx1 = document.getElementById("ticketChart").getContext("2d");
    const labels = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    const incidentStats1 = @json($incidentStatsByYear);
    const incidentStatsArray = Object.values(incidentStats1);
    console.log(incidentStatsArray);
    const openTicketsData = incidentStatsArray.map(stat => stat.Open);
    const closedTicketsData = incidentStatsArray.map(stat => stat.Closed);
    const progressTicketsData = incidentStatsArray.map(stat => stat.in_progress);
    const reviewTicketsData = incidentStatsArray.map(stat => stat.Feedback);
   
    new Chart(ctx1, {
        type: "line",
        data: {
            labels: labels,
            datasets: [
                {
                    label: "Open Ticket",
                    data: openTicketsData,
                    backgroundColor: "rgba(37,111,34, 0.1)",
                    borderColor: "rgba(37,111,34, 1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: "Closed ",
                    data: closedTicketsData,
                    backgroundColor: "rgba(51,49,50, 0.1)",
                    borderColor: "rgba(51,49,50, 1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: "In Progress ",
                    data: progressTicketsData,
                    backgroundColor: "rgba(222,180,16, 0.1)",
                    borderColor: "rgba(222,180,16, 1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: "Feedback ",
                    data: reviewTicketsData,
                    backgroundColor: "rgba(253,97,0, 0.1)",
                    borderColor: "rgba(253,97,0, 1)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    title: {
                        display: true,
                        text: "Tickets",
                    },
                },
            },
        },
    }); </script>

@endif

@endsection