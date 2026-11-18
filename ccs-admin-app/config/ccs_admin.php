<?php
return ['owner_emails'=>array_values(array_filter(array_map('trim',explode(',',strtolower((string)env('CCS_OWNER_EMAILS',env('CCS_WARRANTY_APPROVER_EMAILS',''))))))),
'locales'=>['en'=>'English','si'=>'සිංහල Connect · සිංහල + English','ta'=>'தமிழ் Connect · தமிழ் + English','si_LK'=>'සිංහල','ta_LK'=>'தமிழ்']];
