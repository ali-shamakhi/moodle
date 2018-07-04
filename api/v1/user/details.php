<?php

if (!file_exists('../../../config.php')) {
    header('Location: ../../../install.php');
    die;
}

require_once('../../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot .'/login/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once ('../../util.php');

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
 *   tags={"Attendance"},
 *   @SWG\Parameter(
 *      name="authorization",
 *      in="header",
 *      type="string",
 *      description="Access token for authorization"
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="Details about user login",
 *      @SWG\Schema(
 *          ref="#/definitions/LoginDetailsResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="Unknown Error"
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

$logged_in = false;
$full_name = null;
if (isset($userid_accessdomain->userid) && $userid_accessdomain->userid != 0) {
    $user_record = $DB->get_record('user', array('id'=>$userid_accessdomain->userid));
    if ($user_record) {
        $logged_in = true;
        $full_name = $user_record->firstname.' '. $user_record->lastname;
    } else {
        http_response_code(500);
        echo '{"error": "Coding Error: There is an access token for a user that doesn\'t exists!"}';
        die;
    }
}

$user_details_response = new LoginDetailsResponse((isset($userid_accessdomain->userid) ? $userid_accessdomain->userid : 0), $logged_in, $full_name);

echo json_encode($user_details_response);

