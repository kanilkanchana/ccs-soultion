<?php
return [
    // Comma-separated existing STAFF login email addresses, configured by the system owner.
    'approver_emails' => array_values(array_filter(array_map('trim', explode(',', strtolower((string) env('CCS_WARRANTY_APPROVER_EMAILS', '')))))),
];
