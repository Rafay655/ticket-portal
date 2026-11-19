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
                href="javascript:;">Dashoard</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Dashboard</h6>
</nav>
@endsection
@section('content')
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


@endsection

@section('script')
@if (
    auth()->user()->can('admindashboard.index') ||
    in_array(auth()->user()->designation_id, $allowedDesignations)
)
<script>

const ticketData = @json($ticketTypePercentages); // Your PHP variable
    const ticketSubtypeData = @json($ticketSubTypePercentages); // Your PHP variable

    const labelsTicketType = ticketData.map(item => item.tickettype.name); // Assuming 'name' is the property for ticket type names
    const dataTicketType = ticketData.map(item => item.counter);
    const colorTicketType = ticketData.map(item => item.color);

    const labelsTicketSubType = ticketSubtypeData.map(item => item.ticketsubtype.name); // Assuming 'name' is the property for ticket type names
    const dataTicketSubType = ticketSubtypeData.map(item => item.counter);
    const colorTicketSubType = ticketSubtypeData.map(item => item.color);
  
    const pieLabelsLine = {
        id: "pieLabelsLine",
        afterDraw(chart) {
            const {
                ctx,
                chartArea: { width, height, top, left },
            } = chart;
            const cx = left + width / 2;
            const cy = top + height / 2;
            const sum = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);

            chart.data.datasets.forEach((dataset, i) => {
                chart.getDatasetMeta(i).data.forEach((datapoint, index) => {
                    const { x: a, y: b } = datapoint.tooltipPosition();
                    const outerRadius = datapoint.outerRadius;
                    const x =
                        cx +
                        ((a - cx) / Math.sqrt(Math.pow(a - cx, 2) + Math.pow(b - cy, 2))) *
                        outerRadius;
                    const y =
                        cy +
                        ((b - cy) / Math.sqrt(Math.pow(a - cx, 2) + Math.pow(b - cy, 2))) *
                        outerRadius;
                    const halfwidth = width / 2;
                    const halfheight = height / 2;
                    const xLine = x >= halfwidth ? x + 20 : x - 20;
                    const yLine = y >= halfheight ? y + 20 : y - 20;
                    const extraLine = x >= halfwidth ? 10 : -10;
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                    ctx.fill();
                    ctx.moveTo(x, y);
                    ctx.lineTo(xLine, yLine);
                    ctx.lineTo(xLine + extraLine, yLine);
                    ctx.strokeStyle = dataset.backgroundColor[index];
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                    const percentage =
                        ((chart.data.datasets[0].data[index] ) );
                    ctx.font = "10px Arial";
                    const textXPosition = x >= halfwidth ? "left" : "right";
                    const plusFivePx = x >= halfwidth ? 5 : -5;
                    ctx.textAlign = textXPosition;
                    ctx.textBaseline = "middle";
                    ctx.fillStyle = dataset.backgroundColor[index];

                    ctx.fillText(percentage, xLine + extraLine + plusFivePx, yLine);
                });
            });
        },
    };

    // Initialize first doughnut chart
    const ctx = document.getElementById("myChart").getContext("2d");
    const myChart = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: labelsTicketType,
            datasets: [
                {

                    data: dataTicketType,
                    backgroundColor: colorTicketType,
                    borderColor: colorTicketType,
                    borderWidth: 1,
                    cutout: "70%"
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                  
                },
            },
            layout: {
                padding: {
                    left: 20,
                    right: 20,
                    top: 20,
                    bottom: 20,
                },
            },
        },
        plugins: [pieLabelsLine],
    });

    // Initialize second doughnut chart
    const ctx2 = document.getElementById("myDoughnutChart").getContext("2d");
    const myDoughnutChart = new Chart(ctx2, {
        type: "doughnut",
        data: {
            labels: labelsTicketSubType,
            datasets: [
                {

                    data: dataTicketSubType,
                    backgroundColor: colorTicketSubType,
                    borderColor: colorTicketSubType,
                    borderWidth: 1,
                    cutout: "70%"
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 30,
                    bottom: 30,
                },
            },
            plugins: {
                legend: {
                    display: false,
                 
                },
            },
        },
        plugins: [pieLabelsLine],
    });


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
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true,
                    max: 100,
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