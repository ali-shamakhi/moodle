<?php

if (!file_exists('../../../config.php')) {
    header('Location: ../../../install.php');
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

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
 *   @SWG\Parameter(
 *      name="params",
 *      in="query",
 *      type="string",
 *      format="application/json",
 *      description="The course's ID and the list of present students' IDs",
 *      default=
"
{
   ""course_id"":2,
   ""attend_students"":[
      7,
      21
   ]
}
",
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

$params = json_decode($_GET["params"]);
$course_id = $params->course_id;
$attend_students = $params->attend_students;

// TODO: implement
// mock data
$result = new CourseAttendResponse(1, null);

echo json_encode($result);

?>
