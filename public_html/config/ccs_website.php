<?php
return [
 'enabled'=>env('CCS_WEBSITE_ENABLED',env('APP_ENV')==='local'),
 'origins'=>array_filter(array_map('trim',explode(',',(string)env('CCS_WEBSITE_ORIGINS','http://127.0.0.1:8000,http://localhost:8000,https://ccssolutions.lk,https://www.ccssolutions.lk,https://admin.ccssolutions.lk')))),
];
