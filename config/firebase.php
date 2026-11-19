<?php

return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', ''),       // may be absolute or relative or empty
        'json' => env('FIREBASE_CREDENTIALS_JSON', ''),  // inline JSON or empty
    ],
];
