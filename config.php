<?php

$config = array(
        "input_filename"        => hash('sha', $_SERVER["REMOTE_ADDR"]), // put something unique here, though I have no idea what this was for :D
        "upload_dir"            => 'i', // final image destination
        "logs_dir"              => 'logs', // where logs are stored
        "max_filesize"          => '10485760', // max file size (in bytes)
        "files_limit"           => '100' // global limit of how many files can be stored
);

?>
