<?php

$config = (object) array
(
    "allowed_hosts"  => 'localhost', // comma-separated list of allowed HTTP_HOST, simply your domain name
    "webroot"        => '/', // uploader path or web root with trailing slash
    "upload_dir"     => 'i', // final image destination, w/o leading nor trailing slash
    "logs_dir"       => 'logs', // where logs are stored, w/o leading nor trailing slash
    "max_filesize"   => '10485760', // max file size (in bytes)
    "files_limit"    => '1000', // global limit of how many files can be stored
    "ssl"            => true, // redirects to https
    "csp"            => true, // adds a Content-Security-Policy (CSP) security header with random nonce
    "sri"            => true, // adds a Subresource Integrity (SRI) hash for css/js integrity checks
    "paranoya"       => false, // (experimental) https://github.com/c0m4r/up/wiki/paranoya-integration-(experimental)
    "paranoya_host"  => '127.0.0.1', // (experimental) paranoya --listen-host
    "paranoya_port"  => 1337 // (experimental) paranoya --listen-port
);

?>
