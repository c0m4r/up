<?php

declare(strict_types=1);

$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$lang_code = is_string($accept_language) ? substr($accept_language, 0, 2) : '';

// Allowlist, the code ends up in a file path
if (!in_array($lang_code, ['fr', 'ja', 'ko', 'pl', 'uk'], true)) {
    $lang_code = 'en';
}

require __DIR__ . "/lang/$lang_code.php";
