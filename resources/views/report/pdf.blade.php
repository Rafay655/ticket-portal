<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 80px 50px;
            /* Top, Right, Bottom, Left */
        }

        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .date-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0px 0 0px;
            padding-bottom: 30px ;
            font-size: 14px;
        }

        .bottom-border {
            min-width: 150px;
            max-width: 150px;
            display: inline-block;

        }

        .label-unique-header {
            font-weight: 600;

            /* min-width: 50px; */
            display: inline-block;
        }
    </style>
</head>

<body>
    <header>
        <div
            style="width: 100%; text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 10px ;margin-top:20px">
            <div style="position:absolute;top:15;left:0">
                <img src="{{ public_path('website/img/logo.png') }}" alt="Logo"
                    style="max-height: 30px; margin-bottom: 5px;">
            </div>

            <h2 style="margin: 0; font-size: 14px;">{{$header}}</h2>
            <p style="margin: 0; font-size: 12px;">Generated on: {{ \Carbon\Carbon::now()->format('d-M-Y H:i') }}</p>
        </div>

    </header>
    <footer>
        <div style="width: 100%; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; font-size: 10px;">
            <p style="margin: 0;">Powered by</p>
            <img src="{{ public_path('assets/img/logos/Flexi_FF-01.png') }}" alt="Logo"
                style="max-height: 15px; margin-bottom: 5px;">
        </div>

    </footer>
    <main>
        <div>
            <div class="date-area">
                <span style="display:flex;align-items: center">
                    <div class="label-unique-header"> From:</div>
                    <div class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['startDate'])->format('d-F-Y') }}
                    </div>
                </span>
                <span style="display:block;align-items: center">
                    <span class="label-unique-header"> To:</span>
                    <span class="bottom-border">{{ \Carbon\Carbon::parse($filterSummary['endDate'])->format('d-F-Y') }}
                    </span>
                </span>
                @switch($activeTab)
                    @case(1)
                    <span style="display:block;align-items: center">
                    <span class="label-unique-header"> Status:</span>
                    <span class="bottom-border"> {{ucfirst($filterSummary['status'])}}</span>
                </span>
                        @break
                    @case(2)
                   
                        @break
                    @case(3)
                    <span style="display:block;align-items: center">
                    <span class="label-unique-header"> Assigned To:</span>
                    <span class="bottom-border"> {{ucfirst($filterSummary['userName'])}}</span>
                        @break
                    @case(4)
                    
                        @break
                    @case(5)
                    <span style="display:block;align-items: center">
                    <span class="label-unique-header"> Assigned To:</span>
                    <span class="bottom-border"> {{ucfirst($filterSummary['userName'])}}</span>
                        @break
                    @default
                        No Filter
                @endswitch
                
            </div>
        </div>
        {!! $body !!}
    </main>
</body>

</html>