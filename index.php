<?php

require_once('../../config.php');
require_once("lib.php"); 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

$id = required_param('id', PARAM_INT);           // Course ID
 
// Ensure that the course specified is valid
if (!$course = $DB->get_record('course', array('id'=> $id))) {
    print_error('Course ID is incorrect');
}

$PAGE->set_url('/mod/cloudinary/index.php', array('id'=>$id));

redirect("$CFG->wwwroot/course/view.php?id=$id");
