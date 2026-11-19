<!DOCTYPE html>
<html>
<head>
    <style>
        body{
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
            border-bottom: 2px solid black;
        }

        .top-img img, .top-img h4 {
            margin: 0;
        }

        .top-img img {
            width: 75px;
        }

        .date-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 30px 0 50px;
            padding: 20px 0;
            font-size: 14px;
        }

        .bottom-border {
            min-width: 150px;
            max-width: 150px;
            display: inline-block;
            
        }
        .label-unique-header{
            font-weight: 600;

            /* min-width: 50px; */
            display: inline-block;
        }

    </style>
</head>
<body>
    <div class="top-img">
        <img src="{{ asset('website/img/logo.png') }}" alt="HRPSP Logo">
        
    </div>
    <div class="header">
        <h1>Ticket Detail Report</h1>
    </div>
    <div class="date-area">
        <span style="display:block"> <div class="label-unique-header" > From:</div> <p class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['startDate'])->format('d-F-Y') }}
        </p>  </span>
        <span style="display:block"> <span class="label-unique-header" > To:</span> <p class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['endDate'])->format('d-F-Y') }}
        </p></span>
        <span style="display:block"> <span class="label-unique-header" > Status:</span> <p class="bottom-border"> {{ucfirst( $filterSummary['status'])}}</p>  </span>
    </div>
   
</body>
</html>
