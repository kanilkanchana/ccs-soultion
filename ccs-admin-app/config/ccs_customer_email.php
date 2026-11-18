<?php
return ['enabled'=>(bool)env('CCS_CUSTOMER_EMAIL_OTP_ENABLED',false),'mailer'=>env('CCS_CUSTOMER_EMAIL_MAILER','smtp'),'minutes'=>10,'attempts'=>5,'resend_seconds'=>60];
