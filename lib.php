<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

// import composer autoload
require_once __DIR__ . '/vendor/autoload.php';

function cloudinary_add_instance($label)
{
    global $DB, $CFG;

    // get draft file id
    $draftitemid = file_get_submitted_draft_itemid('attachment');

    // search file in database
    $results = $DB->get_record('files', array('itemid' => $draftitemid));

    // arrange file path
    $baseurl = "$CFG->wwwroot/draftfile.php/$results->contextid/$results->component/$results->filearea/$results->itemid/$results->filename";

    // configure cloudinary
    $config = get_config('cloudinary');
    \Cloudinary::config(array( 
        "cloud_name" => $config->cloudname, 
        "api_key" => $config->api_key, 
        "api_secret" => $config->api_secret, 
        "secure" => $config->secure
    ));

    // upload to cloudinary
    // $upload = \Cloudinary\Uploader::upload(image_to_base64($baseurl));
    $upload = \Cloudinary\Uploader::upload($baseurl);

    // insert cloudinary url to database
    $label->url = $upload['secure_url'];
    $label->timemodified = time();

    $label->id = $DB->insert_record("cloudinary", $label);

    // delete draft record from database
    // $DB->delete_records("files", array("itemid" => $draftitemid));

    // // unlink file draft
    // if (is_file($baseurl)) {
    //     unlink($baseurl);
    // }

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

function image_to_base64($path)
{
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}