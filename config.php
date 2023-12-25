<?php

$config = (object) array
(
    "allowed_hosts"  => 'localhost', // comma-separated list of allowed HTTP_HOST
    "upload_dir"     => 'i', // final image destination
    "logs_dir"       => 'logs', // where logs are stored
    "max_filesize"   => '10485760', // max file size (in bytes)
    "files_limit"    => '1000', // global limit of how many files can be stored
    "csp"            => true, // adds a Content-Security-Policy (CSP) security header with random nonce
    "sri"            => true // adds a Subresource Integrity (SRI) hash for css/js integrity checks
);

?>
