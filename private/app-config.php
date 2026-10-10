<?php
/**
 * Aquaculture Scientific author portal configuration.
 * Upload this file to: domains/aquaculturescientific.com/private/app-config.php
 * This private folder must be BESIDE public_html, not inside it.
 * Fill in both passwords before use. Do not commit this file to GitHub.
 */
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'u613936574_logdetails',
        'user' => 'ACSSubmission',
        'password' => 'Acsallsubmission2026',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => 465,
        'smtp_username' => 'submissions@aquaculturescientific.com',
        'smtp_password' => '1@Axi0CGk',
        'from_email' => 'submissions@aquaculturescientific.com',
        'from_name' => 'Aquaculture Scientific',
        'to_email' => 'submissions@aquaculturescientific.com',
        'to_name' => 'Editorial Office',
    ],
    'storage_path' => __DIR__ . '/uploads',
];
