<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// import composer autoload
require_once __DIR__ . '/vendor/autoload.php';

function cloudinary_add_instance($label)
{
    global $DB, $CFG, $COURSE;
    _cloudinaryConfig();
    
    $image                  = new stdClass;
    $image->itemId          = time();
    $image->component       = 'cloudinary';
    $image->tabel_record    = 'cloudinary';
    $image->filearea        = 'my_filemanager';
    $image->contextId       = context_module::instance($label->coursemodule)->id;
    $image->timemodified    = time();
    $image->draftitemid     = file_get_submitted_draft_itemid($image->filearea);

    // simpan file draft menjadi file yang sesungguhnya
    file_save_draft_area_files($image->draftitemid, $image->contextId, $image->component, $image->filearea, $image->itemId, array('subdirs' => false, 'maxfiles' => 1));

    // ambil file draft yang sudah direkam dalam database
    $results = $DB->get_record('files', array('itemid' => $image->draftitemid));
    $image->filepath = $results->filepath;
    $image->filename = $results->filename;

    // arrange file path
    // sample correct path
    // http://localhost/pluginfile.php/84/mod_assign/introattachment/0/Official-Logo.png
    // $image->baseurl = "$CFG->wwwroot/pluginfile.php/$image->contextId/$image->component/$image->filearea/$image->itemId/$image->filename";
    $fs = get_file_storage();
    $file = $fs->get_file($image->contextId, $image->component, $image->filearea, $image->itemId, $image->filepath, $image->filename);
    $image->baseurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);

    // upload to cloudinary
    // $upload = \Cloudinary\Uploader::upload($image->baseurl);

    // insert cloudinary url to database
    $label->url = $image->baseurl;
    $label->timemodified = $image->timemodified;
    $label->id = $DB->insert_record($image->tabel_record, $label);

    $completiontimeexpected = !empty($label->completionexpected) ? $label->completionexpected : null;
    \core_completion\api::update_completion_date_event($label->coursemodule, 'cloudinary', $label->id, $completiontimeexpected);

    return $label->id;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function cloudinary_delete_instance($id) {
    global $DB;

    if (! $label = $DB->get_record("cloudinary", array("id"=>$id))) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('cloudinary', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'cloudinary', $label->id, null);

    if (! $DB->delete_records("cloudinary", array("id"=>$label->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $page       page object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function cloudinary_view($page, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $page->id
    );

    $event = \mod_page\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('cloudinary', $page);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

function getContextId()
{
    global $COURSE;
    $context = context_course::instance($COURSE->id);
    return $context->id;
}

/**
 * To configure cloudinary
 */
function _cloudinaryConfig()
{
    // configure cloudinary
    $config = get_config('cloudinary');
    return \Cloudinary::config(array( 
        "cloud_name" => $config->cloudname, 
        "api_key" => $config->api_key, 
        "api_secret" => $config->api_secret, 
        "secure" => $config->secure
    ));
}