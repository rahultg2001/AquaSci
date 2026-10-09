
<?php

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function showPage(string $title, string $message, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');

    $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $messageSafe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $titleSafe . '</title>';
    echo '<link rel="stylesheet" href="css/style.css"></head><body>';
    echo '<main class="wrap section"><div class="card">';
    echo '<h2>' . $titleSafe . '</h2>';
    echo '<p>' . $messageSafe . '</p>';
    echo '<p><a href="submit.html">Return to submission form</a></p>';
    echo '</div></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showPage(
        'Submission form required',
        'Please submit your manuscript using the submission form.',
        405
    );
}

function field(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

$name     = field('name');
$email    = field('email');
$org      = field('org');
$country  = field('country');
$orcid    = field('orcid');
$type     = field('type');
$subject  = field('subject');
$title    = field('title');
$abstract = field('abstract');
$cover    = field('cover');

if (
    $name === '' || $org === '' || $country === '' ||
    $type === '' || $subject === '' || $title === '' ||
    $abstract === ''
) {
    showPage('Missing information', 'Please complete all required fields.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showPage('Invalid email', 'Enter a valid email address.', 400);
}

if (field('originality_confirmed') !== 'yes') {
    showPage('Confirmation required', 'Confirm the originality declaration.', 400);
}

if (!isset($_FILES['manuscript'])) {
    showPage('Missing manuscript', 'Please attach your Word document.', 400);
}

$file = $_FILES['manuscript'];

if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
    showPage('Upload failed', 'Please upload your manuscript again.', 400);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, ['doc', 'docx'], true)) {
    showPage('Invalid file', 'Upload a .doc or .docx file.', 400);
}

if ($file['size'] > 20 * 1024 * 1024) {
    showPage('File too large', 'The maximum manuscript size is 20 MB.', 400);
}

$configPath = __DIR__ . '/private/mail-config.php';

if (!is_file($configPath)) {
    error_log('Submission error: mail-config.php is missing.');
    showPage('Configuration error', 'The email service is not configured.', 500);
}

$config = require $configPath;

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email, $name);

    $safeTitle = preg_replace('/[\r\n]+/', ' ', $title);
    $mail->Subject = 'New manuscript submission: ' . $safeTitle;

    $mail->Body =
        "New manuscript submission: Aquaculture Scientific\n\n" .
        "Corresponding author: $name\n" .
        "Email: $email\n" .
        "Institution: $org\n" .
        "Country: $country\n" .
        "ORCID: $orcid\n" .
        "Article type: $type\n" .
        "Primary subject: $subject\n" .
        "Title: $title\n\n" .
        "Abstract:\n$abstract\n\n" .
        "Cover letter:\n$cover\n\n" .
        "Originality declaration: Confirmed\n";

    $filename = basename($file['name']);
    $mail->addAttachment($file['tmp_name'], $filename);
    $mail->send();

    showPage(
        'Submission received',
        'Your manuscript was sent successfully to the editorial office.'
    );

} catch (\Throwable $e) {
    error_log('Submission email error: ' . $e->getMessage());

    showPage(
        'Submission unsuccessful',
        'The manuscript could not be sent. Please contact the editorial office.',
        500
    );
}
