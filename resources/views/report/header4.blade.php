<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            margin: 0;
        }

        .top-img {
            position: absolute;
            top: 0px;
            left: 0px;
        }

        .header {
            text-align: center;
            padding-bottom: 40px;
            border-bottom: 2px solid gray;
        }

        .top-img img,
        .top-img h4 {
            margin: 0;
        }

        .top-img img {
            width: 75px;
        }

        .date-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            font-size: 14px;
        }

        .bottom-border {
            min-width: 150px;
            display: inline-block;
            text-align: center;
        }

        .label-unique-header {
            /* min-width: 50px; */
            display: inline-block;
            font-weight: 600;

        }
    </style>
</head>

<body>
    <div class="top-img">
        <img src="{{ asset('website/img/logo.png') }}" alt="HRPSP Logo">

    </div>
    <div class="header">
        <h1>Ticket Status Report</h1>
    </div>
    <div class="date-area">
    <span style="display:block"> <div class="label-unique-header" > From:</div> <p class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['startDate'])->format('d-F-Y') }}
        </p>  </span>
        <span style="display:block"> <span class="label-unique-header" > To:</span> <p class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['endDate'])->format('d-F-Y') }}
        </p></span>
    </div>

</body>

</html>