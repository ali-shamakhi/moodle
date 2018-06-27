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
$attend_students = $attend_details->attend_students;

// TODO: implement
// TODO: check user privileges
// mock data
$result = new CourseAttendResponse( null);

echo json_encode($result);
