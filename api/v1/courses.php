<?php

if (!file_exists('../../config.php')) {
    header('Location: ../../install.php');
    die;
}

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

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
 *   parameters={},
 *   @SWG\Response(
 *      response=200,
 *      description="List of the user's courses",
 *      @SWG\Schema(
 *          ref="#/definitions/CoursesResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="Unexpected Error"
 *   )
 * )
 */

$courses = new CoursesResponse;

// TODO: populate $courses

echo json_encode($courses);

?>
