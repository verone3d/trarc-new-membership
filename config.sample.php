<?php
/**
 * TRARC Membership Application - configuration template.
 *
 * Copy this file to config.php and fill in real values before deploying.
 * config.php is excluded from web access by .htaccess, but never commit
 * a copy of config.php with real credentials to any public repository.
 */

return [
    // SMTP connection for the mailbox that will send applications to the board.
    'smtp_host' => 'smtp.ionos.com',       // TODO: confirm exact IONOS SMTP host for the mailbox used
    'smtp_port' => 587,
    'smtp_secure' => 'tls',                // 'tls' (STARTTLS, port 587) or 'ssl' (implicit TLS, port 465)
    'smtp_username' => 'CHANGE_ME@yourclubdomain.org',
    'smtp_password' => 'CHANGE_ME',

    // "From" address shown on outgoing mail. Should normally match smtp_username
    // or be an address the mailbox is allowed to send as.
    'from_email' => 'CHANGE_ME@yourclubdomain.org',
    'from_name' => 'TRARC Membership Application',

    // One or more board addresses that should receive every submission.
    'board_recipients' => [
        'CHANGE_ME_board@yourclubdomain.org', // TODO: club to supply real board email address(es)
    ],

    // If true, and the applicant provided an email address, send them a short
    // confirmation copy. Failure to send this confirmation never blocks the
    // board notification email.
    'send_applicant_confirmation' => true,
];
