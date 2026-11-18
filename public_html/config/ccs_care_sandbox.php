<?php
return [
    'hosted_test_enabled' => env('CCS_CARE_HOSTED_TEST_ENABLED', false),
    'enabled' => env('CCS_CARE_SANDBOX_ENABLED', false),
    'customer_id' => (int) env('CCS_CARE_TEST_CUSTOMER_ID', 0),
    'home_url' => env('CCS_CARE_HOME_URL', 'https://ccssolutions.lk'),
    'otp_template' => env('CCS_CARE_OTP_TEMPLATE', ''),
    'language' => env('CCS_CARE_TEMPLATE_LANGUAGE', 'en_US'),
    'notifications_enabled' => env('CCS_CARE_NOTIFICATIONS_ENABLED', false),
    'invoice_template' => env('CCS_CARE_INVOICE_TEMPLATE', ''),
    'payment_template' => env('CCS_CARE_PAYMENT_TEMPLATE', ''),
    'serial_template' => env('CCS_CARE_SERIAL_TEMPLATE', ''),
    'status_template' => env('CCS_CARE_STATUS_TEMPLATE', ''),
];
