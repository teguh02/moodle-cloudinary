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
    global $DB, $CFG;
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
    $results = $DB->get_record('files', array('itemid' => $image->itemId));

    // cek permission ke filedir
    if (!is_writable($CFG->dataroot . '/filedir/')) {
        print_error("The " . $CFG->dataroot . '/filedir/' . ' are not writable');
        die();
    }

    // cari file yang disimpan kedalam folder moodledata
    $image->file_in_moodle_data = rsearch($CFG->dataroot . '/filedir/', "/" . $results->contenthash);

    // cek apakah folder bisa di write?
    // if (!is_writable(__DIR__ . '/temp/')) {
    //     print_error("The " . __DIR__ . '/temp/' . ' are not writable');
    //     die();
    // }

    // definisikan path folder temp
    // $image->file_in_temp_folder = __DIR__ . '/temp/' . $results->contenthash . '.jpg';

    // copy file ke temp folder
    // if (!copy($image->file_in_moodle_data, $image->file_in_temp_folder)) {
    //     print_error("Failed to copy file $results->contenthash to " . $image->file_in_temp_folder);
    //     die();
    // }

    // upload to cloudinary
    $upload = \Cloudinary\Uploader::upload($image->file_in_moodle_data);

    // insert cloudinary url to database
    $label->url = $upload['secure_url'];
    $label->timemodified = $image->timemodified;
    $label->id = $DB->insert_record($image->tabel_record, $label);

    $completiontimeexpected = !empty($label->completionexpected) ? $label->completionexpected : null;
    \core_completion\api::update_completion_date_event($label->coursemodule, 'cloudinary', $label->id, $completiontimeexpected);

    // hapus file didalam folder temp
    // if (is_file($image->file_in_temp_folder)) {
    //     unlink($image->file_in_temp_folder);
    // }

    // hapus file didalam folder moodledata
    if (is_file($image->file_in_moodle_data)) {
        unlink($image->file_in_moodle_data);
    }

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

function rsearch($folder, $pattern) {
    $iti = new RecursiveDirectoryIterator($folder);
    foreach(new RecursiveIteratorIterator($iti) as $file){
         if(strpos($file , $pattern) !== false){
            return $file;
         }
    }
    return false;
}