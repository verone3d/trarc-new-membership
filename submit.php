<?php
require_once __DIR__ . '/includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// --- Honeypot -------------------------------------------------------------
// Real applicants never see or fill in this field. If it has a value,
// silently pretend to succeed so bots don't learn anything useful.
if (!empty($_POST['website'])) {
    render_message(
        'Application Received',
        'Thank you — your application has been received and sent to the TRARC board for review.'
    );
    exit;
}

// --- Simple rate limit ------------------------------------------------------
// Deters accidental double-submits and unsophisticated bots without a database.
$now = time();
if (!empty($_SESSION['trarc_last_submit']) && ($now - $_SESSION['trarc_last_submit']) < 30) {
    http_response_code(429);
    render_message(
        'Please wait a moment',
        'It looks like you just submitted this form. Please wait about 30 seconds and try again if this application did not go through.'
    );
    exit;
}

// --- Collect input ----------------------------------------------------------
function field(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

$old = [
    'name' => field('name'),
    'callsign' => field('callsign'),
    'address' => field('address'),
    'city' => field('city'),
    'state' => field('state'),
    'zip' => field('zip'),
    'home_phone' => field('home_phone'),
    'work_phone' => field('work_phone'),
    'cell_phone' => field('cell_phone'),
    'email' => field('email'),
    'license_class' => field('license_class'),
    'license_expires' => field('license_expires'),
    'birthday' => field('birthday'),
    'membership_type' => field('membership_type'),
    'arrl_member' => !empty($_POST['arrl_member']),
    'ares_member' => !empty($_POST['ares_member']),
    'arrl_expires' => field('arrl_expires'),
    'participate' => field('participate'),
    'interests' => field('interests'),
    'agree' => !empty($_POST['agree']),
    'signature' => field('signature'),
    'under18' => field('under18'),
    'parent_signature' => field('parent_signature'),
    'family' => [],
];

for ($i = 1; $i <= 2; $i++) {
    $row = $_POST['family'][$i] ?? [];
    $old['family'][$i] = [
        'name' => trim((string) ($row['name'] ?? '')),
        'callsign' => trim((string) ($row['callsign'] ?? '')),
        'license_class' => trim((string) ($row['license_class'] ?? '')),
        'arrl' => trim((string) ($row['arrl'] ?? '')),
    ];
}

// --- Validate -----------------------------------------------------------
$errors = [];

if ($old['name'] === '') {
    $errors['name'] = 'Name is required.';
}
if ($old['callsign'] === '') {
    $errors['callsign'] = 'Call sign is required.';
}
if ($old['address'] === '') {
    $errors['address'] = 'Address is required.';
}
if ($old['city'] === '') {
    $errors['city'] = 'City is required.';
}
if ($old['state'] === '') {
    $errors['state'] = 'State is required.';
}
if ($old['zip'] === '') {
    $errors['zip'] = 'Zip code is required.';
} elseif (!preg_match('/^\d{5}(-\d{4})?$/', $old['zip'])) {
    $errors['zip'] = 'Enter a valid US zip code (e.g. 15132 or 15132-1234).';
}
if ($old['license_class'] === '') {
    $errors['license_class'] = 'License class is required.';
}
if (!in_array($old['membership_type'], ['Regular', 'Associate', 'Student'], true)) {
    $errors['membership_type'] = 'Please choose a membership type.';
}
if (!in_array($old['participate'], ['Yes', 'No'], true)) {
    $errors['participate'] = 'Please answer this question.';
}
if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}
if (empty($old['agree'])) {
    $errors['agree'] = 'You must agree to the statement above.';
}
if ($old['signature'] === '') {
    $errors['signature'] = 'Typed signature is required.';
}
if (!in_array($old['under18'], ['Yes', 'No'], true)) {
    $errors['under18'] = 'Please answer this question.';
}
if ($old['under18'] === 'Yes' && $old['parent_signature'] === '') {
    $errors['parent_signature'] = 'Parent/guardian signature is required for applicants under 18.';
}

if (!empty($errors)) {
    render_form_page($old, $errors);
    exit;
}

// --- Build and send email -------------------------------------------------
$config = require __DIR__ . '/config.php';

require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$submittedAt = date('Y-m-d H:i:s T');

[$htmlBody, $plainBody] = build_email_bodies($old, $submittedAt);

$mailSent = false;
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = $config['smtp_port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    foreach ($config['board_recipients'] as $recipient) {
        $mail->addAddress($recipient);
    }

    if ($old['email'] !== '') {
        $replyTo = trarc_clean_header_value($old['email']);
        if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, trarc_clean_header_value($old['name']));
        }
    }

    $mail->isHTML(true);
    $mail->Subject = sprintf(
        'New TRARC Membership Application — %s (%s)',
        $old['name'],
        $old['callsign']
    );
    $mail->Body = $htmlBody;
    $mail->AltBody = $plainBody;

    $mail->send();
    $mailSent = true;
} catch (PHPMailerException $e) {
    error_log('TRARC membership application email failed: ' . $e->getMessage());
} catch (\Throwable $e) {
    error_log('TRARC membership application email failed: ' . $e->getMessage());
}

if (!$mailSent) {
    render_message(
        'Something went wrong',
        'We were unable to send your application right now. Please try again in a few minutes, or contact the TRARC board directly.'
    );
    exit;
}

// Best-effort applicant confirmation. Never blocks success even if it fails.
if (!empty($config['send_applicant_confirmation']) && $old['email'] !== '' && filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    try {
        $confirm = new PHPMailer(true);
        $confirm->isSMTP();
        $confirm->Host = $config['smtp_host'];
        $confirm->SMTPAuth = true;
        $confirm->Username = $config['smtp_username'];
        $confirm->Password = $config['smtp_password'];
        $confirm->SMTPSecure = $config['smtp_secure'];
        $confirm->Port = $config['smtp_port'];

        $confirm->setFrom($config['from_email'], $config['from_name']);
        $confirm->addAddress($old['email'], $old['name']);
        $confirm->isHTML(true);
        $confirm->Subject = 'TRARC Membership Application Received';
        $confirm->Body = '<p>Thanks for applying to TRARC — the board has received your application and will follow up.</p>';
        $confirm->AltBody = "Thanks for applying to TRARC - the board has received your application and will follow up.";
        $confirm->send();
    } catch (\Throwable $e) {
        error_log('TRARC applicant confirmation email failed: ' . $e->getMessage());
    }
}

$_SESSION['trarc_last_submit'] = $now;

render_message(
    'Application Received',
    'Thank you — your application has been received and sent to the TRARC board for review.'
);

// ---------------------------------------------------------------------------

function build_email_bodies(array $old, string $submittedAt): array
{
    $lines = [];

    $lines[] = ['Name', $old['name']];
    $lines[] = ['Call Sign', $old['callsign']];
    $lines[] = ['Address', $old['address']];
    $lines[] = ['City', $old['city']];
    $lines[] = ['State', $old['state']];
    $lines[] = ['Zip Code', $old['zip']];
    if ($old['home_phone'] !== '') {
        $lines[] = ['Home Phone', $old['home_phone']];
    }
    if ($old['work_phone'] !== '') {
        $lines[] = ['Work Phone', $old['work_phone']];
    }
    if ($old['cell_phone'] !== '') {
        $lines[] = ['Cell Phone', $old['cell_phone']];
    }
    if ($old['email'] !== '') {
        $lines[] = ['Email', $old['email']];
    }
    $lines[] = ['License Class', $old['license_class']];
    if ($old['license_expires'] !== '') {
        $lines[] = ['License Expires', $old['license_expires']];
    }
    if ($old['birthday'] !== '') {
        $lines[] = ['Birthday', $old['birthday']];
    }
    $lines[] = ['Membership Type', $old['membership_type']];

    $arrlAres = [];
    if ($old['arrl_member']) {
        $arrlAres[] = 'ARRL';
    }
    if ($old['ares_member']) {
        $arrlAres[] = 'ARES';
    }
    $lines[] = ['Member of ARRL/ARES', $arrlAres ? implode(', ', $arrlAres) : 'None'];
    if ($old['arrl_member'] && $old['arrl_expires'] !== '') {
        $lines[] = ['ARRL Membership Expires', $old['arrl_expires']];
    }

    $lines[] = ['Will Participate in Club Activities', $old['participate']];

    if ($old['interests'] !== '') {
        $lines[] = ['Interests in Amateur Radio', $old['interests']];
    }

    $familyLines = [];
    foreach ($old['family'] as $i => $member) {
        if ($member['name'] === '' && $member['callsign'] === '' && $member['license_class'] === '' && $member['arrl'] === '') {
            continue;
        }
        $parts = [];
        if ($member['name'] !== '') $parts[] = 'Name: ' . $member['name'];
        if ($member['callsign'] !== '') $parts[] = 'Call Sign: ' . $member['callsign'];
        if ($member['license_class'] !== '') $parts[] = 'License Class: ' . $member['license_class'];
        if ($member['arrl'] !== '') $parts[] = 'ARRL Member: ' . $member['arrl'];
        $familyLines[] = "Family member $i — " . implode(', ', $parts);
    }

    $lines[] = ['Agreement to TRARC Constitution', $old['agree'] ? 'Agreed' : 'Not agreed'];
    $lines[] = ['Typed Signature', $old['signature']];
    $lines[] = ['Under 18', $old['under18']];
    if ($old['under18'] === 'Yes') {
        $lines[] = ['Parent/Guardian Typed Signature', $old['parent_signature']];
    }
    $lines[] = ['Submitted', $submittedAt];

    // Plain text
    $plain = "New TRARC Membership Application\n\n";
    foreach ($lines as [$label, $value]) {
        $plain .= "$label: $value\n";
    }
    if ($familyLines) {
        $plain .= "\nAdditional Family Members:\n";
        foreach ($familyLines as $fl) {
            $plain .= "$fl\n";
        }
    }

    // HTML
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#222;">';
    $html .= '<h2 style="margin-bottom:4px;">New TRARC Membership Application</h2>';
    foreach ($lines as [$label, $value]) {
        $html .= '<p style="margin:4px 0;"><strong>' . htmlspecialchars($label) . ':</strong> '
            . nl2br(htmlspecialchars($value)) . '</p>';
    }
    if ($familyLines) {
        $html .= '<h3 style="margin-top:16px;margin-bottom:4px;">Additional Family Members</h3>';
        foreach ($familyLines as $fl) {
            $html .= '<p style="margin:4px 0;">' . htmlspecialchars($fl) . '</p>';
        }
    }
    $html .= '</div>';

    return [$html, $plain];
}

function render_form_page(array $old, array $errors): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TRARC Membership Application</title>
    <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="page">
        <header class="page-header">
            <h1>Two Rivers Amateur Radio Club of McKeesport, PA</h1>
            <h2>Membership Application</h2>
            <p class="required-note">Please fill in the required (*) fields for membership.</p>
            <p class="form-level-error">Please correct the highlighted fields below and resubmit.</p>
        </header>
        <?php require __DIR__ . '/includes/form.php'; ?>
        <footer class="page-footer">
            <p>Questions about membership? Contact the TRARC board.</p>
        </footer>
    </div>
    </body>
    </html>
    <?php
}

function render_message(string $title, string $message): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - TRARC Membership Application</title>
    <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="page">
        <header class="page-header">
            <h1>Two Rivers Amateur Radio Club of McKeesport, PA</h1>
        </header>
        <div class="message-box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($message) ?></p>
            <p><a href="index.php">Return to the membership application</a></p>
        </div>
    </div>
    </body>
    </html>
    <?php
}
