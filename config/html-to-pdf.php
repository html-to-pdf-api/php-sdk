<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Bearer token used to authenticate every request to the HTML to PDF API.
    | Create one in the dashboard at https://htmltopdfapi.co.
    |
    */
    'api_key' => env('HTML_TO_PDF_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Override when running against a local or staging API.
    |
    */
    'base_url' => env('HTML_TO_PDF_BASE_URL', 'https://api.htmltopdfapi.co/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds before the HTTP client gives up on a single render. Long
    | documents or `wait_until=networkidle0` flows may need 30-60+.
    |
    */
    'timeout' => env('HTML_TO_PDF_TIMEOUT', 60),
];
