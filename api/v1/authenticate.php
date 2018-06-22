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

//include('../AuthTable.php');

// required JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * @SWG\Post(
 *   path="/authenticate.php",
 *   summary="Authenticate and get access token.",
 *   description="Valid access_domain values are:
""attendance""
",
 *   consumes={"application/json"},
 *   produces={"application/json"},
 *   tags={"Authentication"},
 *   @SWG\Parameter(
 *      name="params",
 *      in="body",
 *      type="string",
 *      format="application/json",
 *      @SWG\Schema(
 *         ref="#/definitions/AuthenticateParameter"
 *      )
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="Authentication Details",
 *      @SWG\Schema(
 *          ref="#/definitions/AuthenticateResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response=400,
 *      description="Bad request",
 *      examples={
"
{
""error"":""access_domain is unknown""
}
",
"
{
""error"":""access_domain is not set""
}
",
"
{
""error"":""redirect_url is not set""
}
"
}
 *   )
 * )
 */

$request_body = file_get_contents('php://input');
if ($request_body === null) {
    http_response_code(400);
    echo '{"error": "bad request body"}';
    die;
}

$check_login_parameter = json_decode($request_body);

//$check_login_parameter = json_decode($_POST["params"]);
error_log("INIT: input read");

// parameters validation ...

try {
    $redirect_url = $check_login_parameter->redirect_url;
} catch (exception $e) {
    http_response_code(400);
    echo '{"error": "redirect_url is not set"}';
    die;
}

try {
    $access_domain = strtolower($check_login_parameter->access_domain);
    if ($access_domain === null || trim($access_domain) == "")
        throw new Exception();
} catch (exception $e) {
    http_response_code(400);
    echo '{"error": "access_domain is not set"}';
    die;
}

// definitions of valid access domains
const ATDOM = 'ATDOM_';
const ATIME = 'ATIME_'; // prefix of const defining max seconds that an access token is valid after last access
const ATSEC = 'ATSEC_'; // token secret
define('ATDOM_attendance', 1, true);
define('ATIME_attendance', 6 * 60 * 60, true);
define('ATSEC_attendance', 'HSqIO6X%%kTwHVLrp@sak2%EmMeKqM=A', true);

$access_domain_const = ATDOM.$access_domain;
$access_time_const = ATIME.$access_domain;
$access_secret_const = ATSEC.$access_domain;

if(!defined($access_domain_const)) {
    http_response_code(400);
    echo '{"error": "access_domain=\"'.$access_domain.'\" is unknown"}';
    die;
}

error_log("VFY: verified");

// ... all parameters verified above

$current_timestamp = time();

$login_url = get_login_url().'?redirect='.$redirect_url;

if ($USER === null || $USER->id == 0) {
    // user not logged in
    $check_login_response = new AuthenticateResponse(null, $login_url);
    echo json_encode($check_login_response);
    die;
}

$dbman = $DB->get_manager();

// create auth_table properties
$auth_table = new xmldb_table('auth');

$auth_table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$auth_table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
$auth_table->add_field('accessdomain', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
$auth_table->add_field('token', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
$auth_table->add_field('accesseddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

$auth_table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
$auth_table->add_key('useraccessdomain', XMLDB_KEY_UNIQUE, array('userid', 'accessdomain'));
$auth_table->add_key('token', XMLDB_KEY_UNIQUE, array('token'));
$auth_table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

// verify that auth table exists
if(!$dbman->table_exists($auth_table)) {
    $dbman->create_table($auth_table);
}

// create or update user's access token
$auth_record = null;
try {
    $auth_record = $DB->get_record('auth', array('userid'=>$USER->id, 'accessdomain'=>constant($access_domain_const)), '*', MUST_EXIST);
    if ($current_timestamp - $auth_record->accesseddate >= constant($access_time_const))
        $access_token = md5($user_record->username.constant($access_secret_const).$user_record->password.strval($current_timestamp));
    else
        $access_token = $auth_record->token;
    $auth_record_new = new stdClass;
    $auth_record_new->id = $auth_record->id;
    $auth_record_new->token = $access_token;
    $auth_record_new->accesseddate = $current_timestamp;
    $DB->update_record('auth', $auth_record_new);
} catch (dml_exception $dml_ex) {
    // no token found in DB
    $user_record = $DB->get_record('user', array('id'=>$USER->id));
    $auth_record = new stdClass;
    $auth_record->userid = $USER->id;
    $auth_record->accessdomain = constant($access_domain_const);
    $access_token = md5($user_record->username.constant($access_secret_const).$user_record->password.strval($current_timestamp));
    $auth_record->token = $access_token;
    $auth_record->accesseddate = $current_timestamp;
    // insert new record, and verify unique token inserted
    while(!$DB->insert_record('auth', $auth_record, false)) {
        $current_timestamp = time();
        $access_token = md5(strval($current_timestamp*rand(1,1000)).$user_record->username.constant($access_secret_const).$user_record->password);
        $auth_record->token = $access_token;
    }
}

error_log("DREADY: data ready");

$check_login_response = new AuthenticateResponse($access_token, $login_url);

echo json_encode($check_login_response);

