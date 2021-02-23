<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_cloudinary\event\attempt_submitted',
        'includefile' => '/mod/cloudinary/locallib.php',
        'callback' => 'quiz_attempt_submitted_handler',
        'internal' => false,
    ],
];