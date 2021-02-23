<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

function cloudinary_add_instance($label)
{
    global $DB;
    
    $label->url = json_encode($label);
    $label->timemodified = time();

    $label->id = $DB->insert_record("cloudinary", $label);

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