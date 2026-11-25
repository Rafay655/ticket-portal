<table border="1" cellspacing="0" cellpadding="5"  style="width: 100%; border-collapse: collapse;">
    <tr>
        <th>ID</th>
        <th>Ticket Type</th>
        <th>Ticket Category</th>
        <th>Ticket Sub-Category</th>
        <th>Happiness Rating</th>
    </tr>
    @php
        $totalRate = 0;
        $ticketCount = 0;
    @endphp

    @foreach ($ticketData as $ticket)
        <tr>
            <td>{{ $ticket->id }}</td>
            <td>{{ $ticket->tickettype->name }}</td>
            <td>{{ $ticket->ticket_category_name ?? 'Not assigned' }}</td>
            <td>{{ $ticket->ticketsubtype->name }}</td>
            <td style="font-family:sans-serif">
                @for ($i = 1; $i <= 5; $i++)
                    @if ($i <= $ticket->rate)
                        <i class="fas fa-star"></i>
                    @else
                        <i class="far fa-star"></i>
                    @endif
                @endfor
            </td>
        </tr>

        @php
            $totalRate += $ticket->rate;
            $ticketCount++;
        @endphp
    @endforeach

    @php
        $cumulativeRating = $ticketCount > 0 ? $totalRate / $ticketCount : 0;
        $fullStars = floor($cumulativeRating);
        $check = $cumulativeRating - $fullStars;
        $has3QuaterStar = false;
        $hasHalfStar = false;
        $hasQuaterStar = false;
        $flag = false;
        if ($check >= 0.6 && $check <= 0.99) {
            $has3QuaterStar = true;
            $flag = true;
        } else if ($check >= 0.3 && $check <= 0.59) {
            $hasHalfStar = true;
            $flag = true;
        } else if ($check >= 0 && $check <= 0.29) {
            $hasQuaterStar = true;
            $flag = true;
        }

    @endphp

    <tr>
        <td colspan="3" style="text-align:right;"><strong>Cumulative Happiness Rating:</strong></td>
        <td style="font-family:sans-serif">
            <strong>{{ number_format($cumulativeRating, 2) }}</strong>

            @for ($i = 1; $i <= $fullStars; $i++)
                <i class="fas fa-star"></i>
            @endfor

            @if ($hasHalfStar)
                <i class="fas fa-star-half-alt"></i>
            @endif
            @if ($hasQuaterStar)
                <svg xmlns="http://www.w3.org/2000/svg" style="position:relative;bottom:-5px" width="22" height="22"
                    fill="none" viewBox="0 0 16 16" id="star-one-quarter">
                    <path fill="#000"
                        d="M7.55167 1.77872L5.75771 5.41369L1.74627 5.99659C1.33616 6.05618 1.17241 6.56016 1.46917 6.84943L4.37187 9.67887L3.68663 13.6741C3.61658 14.0825 4.04529 14.394 4.41211 14.2012L8.00004 12.3149L11.588 14.2012C11.9548 14.394 12.3835 14.0825 12.3134 13.6741L11.6282 9.67887L14.5309 6.84943C14.8277 6.56016 14.6639 6.05618 14.2538 5.99659L10.2424 5.41369L8.44841 1.77872C8.265 1.40709 7.73508 1.40709 7.55167 1.77872ZM6.16164 6.3655C6.32449 6.34183 6.46528 6.23955 6.53811 6.09198L8.00004 3.12978L9.46198 6.09198C9.53481 6.23955 9.67559 6.34183 9.83844 6.3655L13.1074 6.84051L10.742 9.14626C10.6241 9.26113 10.5704 9.42663 10.5982 9.58883L11.1566 12.8446L8.23271 11.3074C8.08705 11.2309 7.91303 11.2309 7.76737 11.3074L6 12.2366V6.38899L6.16164 6.3655Z">
                    </path>
                </svg>
            @endif
            @if ($has3QuaterStar)
                <svg xmlns="http://www.w3.org/2000/svg" style="position:relative;bottom:-5px" width="22" height="22"
                    fill="none" viewBox="0 0 16 16" id="star-three-quarter">
                    <path fill="#000"
                        d="M10.2424 5.41369L8.44843 1.77872C8.35636 1.59217 8.17697 1.49926 7.99793 1.5C7.82028 1.50071 7.64296 1.59362 7.55161 1.77872L5.75765 5.41369L1.74621 5.99659C1.3361 6.05618 1.17235 6.56016 1.46911 6.84943L4.37181 9.67887L3.68657 13.6741C3.63115 13.9972 3.88785 14.2596 4.17912 14.2597C4.25608 14.2598 4.33546 14.2415 4.41213 14.2012L8.00002 12.3149L11.5879 14.2012C11.651 14.2343 11.7159 14.2526 11.7798 14.258C11.8075 14.2603 11.835 14.2603 11.862 14.258C12.1358 14.2346 12.3663 13.9819 12.3135 13.6741L11.6282 9.67887L14.5309 6.84943C14.8277 6.56016 14.6639 6.05618 14.2538 5.99659L10.2424 5.41369ZM10.0001 12.2366V6.389L13.1074 6.84051L10.7419 9.14626C10.6241 9.26113 10.5703 9.42663 10.5981 9.58883L11.1565 12.8446L10.0001 12.2366Z">
                    </path>
                </svg>
            @endif


            @for ($i = $fullStars + ($flag ? 1 : 0); $i < 5; $i++)
                <i class="far fa-star"></i>
            @endfor
        </td>
    </tr>
</table>