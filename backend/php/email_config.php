<?php
define('MAIL_PROVIDER', getenv('MAIL_PROVIDER') ?: 'http_brevo'); // http_brevo | http_sendgrid | http_resend | smtp
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
define('RESEND_API_KEY', getenv('RESEND_API_KEY') ?: '');
define('SENDGRID_API_KEY', getenv('SENDGRID_API_KEY') ?: '');

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: 'tu_correo@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');

define('MAIL_FROM', getenv('MAIL_FROM') ?: SMTP_USER);
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'DISTRICARNES HERMANOS NAVARRO');
?>
