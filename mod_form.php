<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/cloudinary/lib.php');

class mod_cloudinary_mod_form extends moodleform_mod {
 
    function definition() {
        global $CFG, $DB, $OUTPUT;
 
        $mform =& $this->_form;
 
        $mform->addElement('filepicker', 'attachment', "Upload a file");
        $mform->addRule('attachment', null, 'required', null, 'client');
        $mform->addElement('static', "description", 'Upload file here and file will be store in your cloudinary account');

        // $mform->addElement('textarea', 'deskripsi', 'Description', 'wrap="virtual" rows="4" width="100%"');
        
        $this->standard_coursemodule_elements();
        $this->add_action_buttons(true, false, null);

        return true;
    }
}