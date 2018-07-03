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
require_once($CFG->dirroot.'/mod/assign/lib.php');

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

$students_ids = array();
foreach (get_role_users(5, $course_context) as $student) {
    array_push($students_ids, $student->id);
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
    $assign_data = get_attendance_assign_data($course_id, $attendance_category_id, $session_start_timestamp, $session_end_timestamp, $local_date_string);
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
foreach ($students_ids as $sid) {
    $grade = new stdClass;
    $grade->userid = $sid;
    $grade->assignment = $attendance_record->id;
    $grade->grade = (in_array($sid, $attend_students) ? 1.0 : 0.0);

    $grade_added = false;
    $grade_record = $DB->get_record('assign_grades', array('userid'=>$grade->userid, 'assignment'=>$grade->assignment));
    if ($grade_record) {
        $grade->id = $grade_record->id;
        $grade_added = $DB->update_record('assign_grades', $grade);
    } else {
        $grade->timecreated = $current_timestamp;
        $grade->timemodified = $current_timestamp;
        $grade->grader = $userid_accessdomain->userid;
        $grade->locked = 0;
        $grade->mailed = 0;
        $grade_added = $DB->insert_record('assign_grades', $grade);
    }

    if ($grade_added) {
        $gradebook_grade = new stdClass;
        $gradebook_grade->userid   = $grade->userid;
        $gradebook_grade->rawgrade = $grade->grade;
        $gradebook_grade->usermodified = $grade->grader;
        $gradebook_grade->datesubmitted = NULL;
        $gradebook_grade->dategraded = $grade->timemodified;
        $gradebook_grade->feedbackformat = 0;
        $gradebook_grade->feedback = '';

//        $agi = clone $attendance_record;
        $attendance_record->cmidnumber = '';  // TODO: check $this->get_course_module()->idnumber;
        $attendance_record->gradefeedbackenabled = true;  //$this->is_gradebook_feedback_enabled();

        $grade_successful = assign_grade_item_update($attendance_record, $gradebook_grade) == GRADE_UPDATE_OK;

        if (!$grade_successful) {
            error_log('ERR . assign_grade_item_update');
        }
    } else {
        error_log('ERR . grade not added');
    }
}

$result = new CourseAttendResponse(null);

echo json_encode($result);
