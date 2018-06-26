<?php

if (!file_exists('../../../config.php')) {
    header('Location: ../../../install.php');
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once('../../util.php');

foreach (glob("../models/*.php") as $class_name) {
    include($class_name);
}

// required JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * @SWG\Get(
 *   path="/course/details.php",
 *   summary="Get details of the course",
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
 *      name="course_id",
 *      in="query",
 *      type="integer"
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="Details of the course",
 *      @SWG\Schema(
 *          ref="#/definitions/CourseDetailsResponse"
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

$course_id = null;
if (!isset($_GET["course_id"])) {
    http_response_code(400);
    echo '{"error":"course_id is not set"}';
    die;
}
$course_id = $_GET['course_id'];

try {
    $course = get_course($course_id);
} catch (dml_exception $dml_ex) {
    http_response_code(404);
    echo '{"error":"course not found"}';
    die;
}

$course_context = context_course::instance($course->id);
$teachers_names = array();
$students_details = array();
foreach (get_role_users(3, $course_context) as $teacher) {
    array_push($teachers_names, $teacher->firstname.' '.$teacher->lastname);
}
foreach (get_role_users(4, $course_context) as $teacher) {
    array_push($teachers_names, $teacher->firstname.' '.$teacher->lastname);
}
foreach (get_role_users(5, $course_context) as $student) {
    array_push($students_details, new StudentDetails($student->id, $student->firstname.' '.$teacher->lastname,
        'http://'.$_SERVER['SERVER_NAME'].'/moodle/user/pix.php/'.$student->id.'/f1.jpg', rand(0,5)));
}

// TODO: really implement
// TODO: check user privileges
// more real mock data
$session_start_timestamp = $current_timestamp - ($current_timestamp % 86400) + (7 * 60 * 60) - date('Z');
$session_end_timestamp = $current_timestamp - ($current_timestamp % 86400) + (19 * 60 * 60) - date('Z');
$MONDAY = 1; $TUESDAY = 2; $WEDNESDAY = 3; $THURSDAY = 4; $FRIDAY = 5; $SATURDAY = 6; $SUNDAY = 7;
$current_weekday = date('N', $current_timestamp);
preg_match_all('/<p>(.*?)<\/p>/', $course->summary, $summary_paragraphs, PREG_PATTERN_ORDER);
$timetable_started = false;
foreach ($summary_paragraphs[1] as $summary_line) {
    $line = strtolower(str_replace('&nbsp;', '', str_replace(' ', '', $summary_line)));
    if ($line == '[timetable]') {
        if ($timetable_started) {
            break;
        }
        $timetable_started = true;
        continue;
    }
    if ($line == '[/timetable]') {
        break;
    }
    if ($timetable_started) {
        preg_match_all('/([a-z]*)([0-9]{1,2}):([0-9]{2})-([0-9]{1,2}):([0-9]{2})/', $line, $time_parts, PREG_PATTERN_ORDER);
        $weekday = get_weekday_from_day_name($time_parts[1][0]);
        if ($weekday == $current_weekday) {
            $session_start_timestamp = $current_timestamp - ($current_timestamp % 86400) + ((int)$time_parts[2][0]) * 60 * 60 + ((int)$time_parts[3][0]) * 60 - date('Z');
            $session_end_timestamp = $current_timestamp - ($current_timestamp % 86400) + ((int)$time_parts[4][0]) * 60 * 60 + ((int)$time_parts[5][0]) * 60 - date('Z');
            break;
        }
    }
}
$course_details = new CourseDetailsResponse(null, $course_id, $session_start_timestamp, $session_end_timestamp, rand(0, 10), $teachers_names, $students_details);

echo json_encode($course_details);

