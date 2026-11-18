<?php

// Phase 1 only. No customer event handlers or production sender support.
return [
    'enabled' => env('CCS_WHATSAPP_TEST_ENABLED', false),
    'access_token' => env('CCS_WHATSAPP_TEST_ACCESS_TOKEN', ''),
    'phone_number_id' => env('CCS_WHATSAPP_TEST_PHONE_NUMBER_ID', ''),
    'recipient' => env('CCS_WHATSAPP_TEST_RECIPIENT', ''),
    // Use the API version shown in your Meta dashboard.
    'graph_version' => env('CCS_WHATSAPP_TEST_GRAPH_VERSION', 'v25.0'),
];
