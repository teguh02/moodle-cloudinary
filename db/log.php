<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$logs = array(
    array('module'=>'cloudinary', 'action'=>'add', 'mtable'=>'cloudinary', 'field'=>'name'),
    array('module'=>'cloudinary', 'action'=>'update', 'mtable'=>'cloudinary', 'field'=>'name'),
);