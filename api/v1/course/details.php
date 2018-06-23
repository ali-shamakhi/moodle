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
 * @SWG\Get(
 *   path="/course/details.php",
 *   summary="Get details of the course",
 *   consumes={"application/json"},
 *   produces={"application/json"},
 *   tags={"Attendance"},
 *   @SWG\Parameter(
 *      name="params",
 *      in="query",
 *      type="string",
 *      format="application/json",
 *      default=
"
{
   ""course_id"":2
}
",
 *      @SWG\Schema(
 *         ref="#/definitions/CourseIdParameter"
 *      )
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

$params = json_decode($_GET["params"]);
$course_id = $params->course_id;

// TODO: implement
// mock data
$course_details = new CourseDetailsResponse(null,
    $course_id, 1528095600, 1528101000, 10, array("Ali Shamakhi"),
    array(new StudentDetails(7, "Masoud Moharrami", "localhost/moodle/user/pix.php/7/f1.jpg", 3)));

echo json_encode($course_details);

?>
