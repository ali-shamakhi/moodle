<?php

if (!file_exists('../../../config.php')) {
    header('Location: ../../../install.php');
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/user/renderer.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/report/grader/lib.php');

require_once('../../util.php');

foreach (glob("../models/*.php") as $class_name) {
    include($class_name);
}

// required JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * @SWG\Post(
 *   path="/course/attend.php",
 *   summary="Post attendance details",
 *   consumes={"application/json"},
 *   produces={"application/json"},
 *   tags={"Attendance"},
 *   @SWG\Parameter(
 *      name="authorization",
 *      in="header",
 *      type="string",
 *      description="Access token for authorization"
 *   ),
 *   @SWG\Parameter(
 *      name="attend_details",
 *      in="body",
 *      type="string",
 *      format="application/json",
 *      description="The course's ID and the list of present students' IDs",
 *      @SWG\Schema(
 *         ref="#/definitions/CourseAttendParameter"
 *      )
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="Attendance details",
 *      @SWG\Schema(
 *          ref="#/definitions/CourseAttendResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="Unexpected Error"
 *   )
 * )
 */

$current_timestamp = time();

$request_headers = apache_request_headers();
if (!isset($request_headers['authorization'])) {
    http_response_code(400);
    echo '{"error": "no authorization header"}';
    die;
}

$userid_accessdomain = get_userid_accessdomain_access_by_token($DB, $current_timestamp, get_access_domain_valid_seconds('attendance'), $request_headers['authorization']);
if ($userid_accessdomain === null) {
    http_response_code(401);
    echo '{"error": "Unauthorized"}';
    die;
}

$request_body = file_get_contents('php://input');
if ($request_body === null) {
    http_response_code(400);
    echo '{"error": "bad request body"}';
    die;
}

try {
    $attend_details = json_decode($request_body);
    if (!isset($attend_details->course_id)) {
        http_response_code(400);
        echo '{"error": "course_id is not set"}';
        die;
    }
    if (!isset($attend_details->attend_students)) {
        http_response_code(400);
        echo '{"error": "attend_students is not set"}';
        die;
    }
} catch (exception $ex) {
    http_response_code(400);
    echo '{"error": "bad request body"}';
    die;
}
$course_id = $attend_details->course_id;
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    http_response_code(404);
    echo '{"error": "no course by given course_id found"}';
    die;
}
$attend_students = $attend_details->attend_students;

// TODO: check user privileges

$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$course_id, 'page'=>null));
$course_context = context_course::instance($course_id);
$report = new grade_report_grader($course_id, $gpr, $course_context);
$gtree_obj = json_decode($report->gtree->exporttojson(null, ''));
if (!isset($gtree_obj->children)) {
    http_response_code(500);
    echo '{"error": "There is a problem in gradebook."}';
    die;
}

$attendance_category_id = null;
foreach ($gtree_obj->children as $grade_item) {
    if ($grade_item->type == 'category' && isset($grade_item->name) && strtolower($grade_item->name) == 'attendance') {
        $attendance_category_id = $grade_item->id;
        error_log('ACI: '.$attendance_category_id);
        break;
    }
}
if ($attendance_category_id == null) {
    // TODO: Automatically create Attendance category
    http_response_code(404);
    echo '{"error": "There is no Attendance category in moodle assignment categories."}';
    die;
}

$course = get_course($course_id);
$session_timestamps = get_course_session_timestamps($course->summary, $current_timestamp);
$session_start_timestamp = $session_timestamps->start;
$session_end_timestamp = $session_timestamps->end;

$a_data = new stdClass;
$successful = true;

try {
    $attendance_record = $DB->get_record_sql('SELECT * FROM {assign} 
 WHERE name LIKE "Attendance %" AND course = ? AND allowsubmissionsfromdate = ?', array($course_id, $session_start_timestamp), MUST_EXIST);
    $a_data->instance = $attendance_record->id;
} catch (dml_exception $dml_ex) {
    $a_data->name = 'Attendance '.date('Y F j', $session_start_timestamp);
    $a_data->course = $course_id;
    $a_data->intro = '';
    $a_data->introformat = FORMAT_HTML;
    $a_data->alwaysshowdescription = 1;
    $a_data->submissiondrafts = 0;
    $a_data->requiresubmissionstatement = 0;
    $a_data->sendnotifications = 0;
    $a_data->sendlatenotifications = 0;
    $a_data->sendstudentnotifications = 1;
    $a_data->allowsubmissionsfromdate = $session_start_timestamp;
    $a_data->duedate = $session_end_timestamp;
    $a_data->grade = 1;
    $a_data->completionunlocked = 1;
    $a_data->completionsubmit = 1;
    $a_data->teamsubmission = 0;
    $a_data->requireallteammemberssubmit = 0;
    $a_data->blindmarking = 0;
    $a_data->maxattempts = -1;
    $a_data->markingworkflow = 0;
    $a_data->markingallocation = 0;
    $successful = assign_update_instance($a_data, null);
}

if ($successful) {
    if (!isset($a_data->instance)) {
        try {
            $attendance_record = $DB->get_record_sql('SELECT * FROM {assign} 
 WHERE name LIKE "Attendance %" AND course = ? AND allowsubmissionsfromdate = ?', array($course_id, $session_start_timestamp), MUST_EXIST);
            $a_data->instance = $attendance_record->id;
        } catch (dml_exception $dml_ex) {
            http_response_code(500);
            echo '{"error": "CODING ERROR: Attendance Assignment record not found after successful insertion!"}';
            die;
        }
    }
} else {
    http_response_code(500);
    echo '{"error": "Unexpected error on creating Attendance Assignment"}';
    die;
}

// TODO: grade students

$result = new CourseAttendResponse(null);

echo json_encode($result);
