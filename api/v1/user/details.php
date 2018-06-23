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
 *   path="/user/details.php",
 *   summary="Reports whether user is logged in or not.",
 *   produces={"application/json"},
 *   tags={"Authentication"},
 *   @SWG\Response(
 *      response=200,
 *      description="Details about user login",
 *      @SWG\Schema(
 *          ref="#/definitions/CheckLoginResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="Unknown Error"
 *   )
 * )
 */


$logged_in = false;
$full_name = null;
if ($USER !== null && $USER->id != 0) {
    $logged_in = true;
    $full_name = $USER->firstname.' '. $USER->lastname;
}

$user_details_response = new CheckLoginResponse($logged_in, $full_name);

echo json_encode($user_details_response);

