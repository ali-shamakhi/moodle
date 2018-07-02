<?php

if (!file_exists('../../../config.php')) {
    header('Location: ../../../install.php');
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot . '/course/modlib.php');
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
$course_record = $DB->get_record('course', array('id' => $course_id));
if (!$course_record) {
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
//        error_log('ACI: '.$attendance_category_id);
        break;
    }
}
if ($attendance_category_id == null) {
    // TODO: Automatically create Attendance category
    http_response_code(404);
    echo '{"error": "There is no Attendance category in moodle assignment categories."}';
    die;
}

$session_timestamps = get_course_session_timestamps($course_record->summary, $current_timestamp);
$session_start_timestamp = $session_timestamps->start;
$session_end_timestamp = $session_timestamps->end;
$local_date_string = $session_timestamps->local_date_string;

//error_log(json_encode(get_fast_modinfo($course_id)));

try {
    $attendance_record = $DB->get_record_sql('SELECT * FROM {assign} 
 WHERE name LIKE "Attendance %" AND course = ? AND allowsubmissionsfromdate = ?', array($course_id, $session_start_timestamp), MUST_EXIST);
} catch (dml_exception $dml_ex) {
    $assign_data = new stdClass;
    $assign_data->coursemodule = 0;
    $assign_data->section = 0;
    $assign_data->module = 1;  // assign
    $assign_data->modulename = 'assign';
    $assign_data->instance = 0;
    $assign_data->add = 'assign';
    $assign_data->update = 0;
    $assign_data->return = 0;
    $assign_data->sr = 0;
    $assign_data->groupmode = 0;
    $assign_data->availabilityconditionsjson = '{"op":"&","c":[],"showc":[]}';
    error_log($local_date_string);
    $assign_data->name = 'Attendance '.$local_date_string;
    $assign_data->course = $course_id;
    $assign_data->intro = '';                  // HIDDEN!
    $assign_data->introformat = FORMAT_HTML;   // HIDDEN!
    $assign_data->showdescription = 0;
    $assign_data->alwaysshowdescription = 1;
    $assign_data->nosubmissions = 1;           // HIDDEN!
    $assign_data->assignsubmission_comments_enabled = 1;
    $assign_data->assignfeedback_comments_enabled = 1;
    $assign_data->assignfeedback_comments_commentinline = 0;
    $assign_data->submissiondrafts = 0;
    $assign_data->requiresubmissionstatement = 0;
    $assign_data->attemptreopenmethod = 'none';
    $assign_data->sendnotifications = 0;
    $assign_data->sendlatenotifications = 0;
    $assign_data->sendstudentnotifications = 1;
    $assign_data->allowsubmissionsfromdate = $session_start_timestamp;
    $assign_data->duedate = $session_end_timestamp;
    $assign_data->cutoffdate = 0;
    $assign_data->gradingduedate = 0;
    $assign_data->grade = 1;
    $assign_data->advancedgradingmethod_submissions = '';
    $assign_data->gradecat = $attendance_category_id;
    $assign_data->gradepass = 1.0;
    $assign_data->completionunlocked = 1;
    $assign_data->completionsubmit = 1;
    $assign_data->completion = 0;
    $assign_data->completionexpected = 0;
    $assign_data->tags = array();
    $assign_data->teamsubmission = 0;
    $assign_data->preventsubmissionnotingroup = 0;
    $assign_data->requireallteammemberssubmit = 0;
    $assign_data->blindmarking = 0;
    $assign_data->revealidentities = 0;        // HIDDEN!
    $assign_data->maxattempts = -1;
    $assign_data->markingworkflow = 0;
    $assign_data->markingallocation = 0;
    $assign_data->visible = 1;
    $assign_data->visibleoncoursepage = 1;
    $assign_data->cmidnumber = '';
    $assign_data->competency_rule = 0;
    try {
        $attendance_moduleinfo = add_moduleinfo($assign_data, get_course($course_id));
    } catch (dml_exception $e) {
        http_response_code(500);
        echo '{"error": "dml_exception: '.$e->getTraceAsString().'"}';
        die;
    } catch (moodle_exception $e) {
        http_response_code(500);
        echo '{"error": "moodle_exception: '.$e->getTraceAsString().'"}';
        die;
    }
}

if (!isset($attendance_record) || !isset($attendance_record->id)) {
    try {
        $attendance_record = $DB->get_record_sql('SELECT * FROM {assign} 
 WHERE name LIKE "Attendance %" AND course = ? AND allowsubmissionsfromdate = ?', array($course_id, $session_start_timestamp), MUST_EXIST);
    } catch (dml_exception $dml_ex) {
          http_response_code(500);
          echo '{"error": "CODING ERROR: Attendance Assignment record not found after successful insertion!"}';
          die;
    }
}

// TODO: grade students

$result = new CourseAttendResponse(null);

echo json_encode($result);
