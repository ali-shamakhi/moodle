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
        'http://localhost/moodle/user/pix.php/'.$student->id.'/f1.jpg', rand(0,5)));    // TODO: generify
}

// TODO: check user privileges

$session_timestamps = get_course_session_timestamps($course->summary, $current_timestamp);
$session_start_timestamp = $session_timestamps->start;
$session_end_timestamp = $session_timestamps->end;

$past_attendance_count = $DB->count_records_sql('SELECT COUNT(id) FROM {assign} WHERE course = '.$course_id.' AND `name` LIKE "Attendance %"');

$course_details = new CourseDetailsResponse(null, $course_id, $session_start_timestamp, $session_end_timestamp, $past_attendance_count, $teachers_names, $students_details);

echo json_encode($course_details);

