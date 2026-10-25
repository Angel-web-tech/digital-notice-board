<?php
declare(strict_types=1);

// Copy to mail.php and use a Gmail App Password, never your normal password.
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-address@gmail.com',
    'password' => 'your-16-character-app-password',
    'from_address' => 'your-address@gmail.com',
    'from_name' => 'Campus Notice Board',
    'secure' => 'tls',
];