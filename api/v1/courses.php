<?php

if (!file_exists('../../config.php')) {
    header('Location: ../../install.php');
    die;
}

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/login/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once ('../util.php');

foreach (glob("models/*.php") as $class_name) {
    include($class_name);
}

// required JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * @SWG\Get(
 *   path="/courses.php",
 *   summary="Get All courses of the user",
 *   produces={"application/json"},
 *   tags={"Attendance"},
 *   @SWG\Parameter(
 *      name="authorization",
 *      in="header",
 *      type="string",
 *      description="Access token for authorization"
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="List of the user's courses",
 *      @SWG\Schema(
 *          ref="#/definitions/CoursesResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response=401,
 *      description="Unauthorized",
 *      examples={
"
{
""error"":""Unauthorized""
}
"
}
 *   ),
 *   @SWG\Response(
 *      response=400,
 *      description="Bad request",
 *      examples={
"
{
""error"":""no authorization header""
}
"
}
 *   )
 * )
 */

//if ($USER === null || $userid == 0) {
//    $courses = new CoursesResponse(-1, "Error: User is not logged in.");
//} else {
//    $mdl_courses = null;
//    try {
//        $mdl_courses = enrol_get_my_courses();
//        $courses = $mdl_courses;;
//    } catch (coding_exception $e) {
//        $courses = new CoursesResponse(-1, "Error: " . $e->getMessage());
//    }
//}

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

$user_id = $userid_accessdomain->userid;
$access_domain = $userid_accessdomain->accessdomain;

$mdl_courses = enrol_get_users_courses($user_id);

error_log(json_encode($mdl_courses));

// TODO: complete course details

$teacher_courses = array();
$student_courses = array();
foreach ($mdl_courses as $course) {
    $course_context = context_course::instance($course->id);
    $user_roles = get_user_roles($course_context, $user_id);
    $teachers_names = array();
    foreach (get_role_users(3, $course_context) as $teacher) {
        array_push($teachers_names, $teacher->firstname.' '.$teacher->lastname);
    }
    foreach (get_role_users(4, $course_context) as $teacher) {
        array_push($teachers_names, $teacher->firstname.' '.$teacher->lastname);
    }
    $is_teacher = false;
    $is_student = false;
    foreach ($user_roles as $role) {
        if ((isset($role->shortname) && strpos(strtolower($role->shortname), 'teacher') !== false) || (isset($role->name) && strpos(strtolower($role->name), 'teacher') !== false)) {
            $is_teacher = true;
            if ($is_student) break;
        }
        if ((isset($role->shortname) && strpos(strtolower($role->shortname), 'student') !== false) || (isset($role->name) && strpos(strtolower($role->name), 'student') !== false)) {
            $is_student = true;
            if ($is_teacher) break;
        }
    }
    if ($is_teacher) {
        array_push($teacher_courses, new TeacherCourse($course->id, $course->fullname));
    }
    if ($is_student) {
        // TODO: absence_count
        array_push($student_courses, new StudentCourse($course->id, $course->fullname, 0, $teachers_names));
    }
}

$courses_response = new CoursesResponse(null, $teacher_courses, $student_courses);

echo json_encode($courses_response);
