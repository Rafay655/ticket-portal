<?php

return [
    // By default use the same first saved calendar shown on the Business Hours screen.
    // Set an ID only when a specific calendar should drive ticket escalation.
    'business_hours_id' => env('TICKET_ESCALATION_BUSINESS_HOURS_ID'),
];