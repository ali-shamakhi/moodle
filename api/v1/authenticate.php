<?php

if (!file_exists('../../config.php')) {
    header('Location: ../../install.php');
    die;
}

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

require_once('../util.php');

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
 *   summary="Get login_url",
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

// parameters validation ...

try {
    $redirect_url = $check_login_parameter->redirect_url;
} catch (exception $e) {
    http_response_code(400);
    echo '{"error": "redirect_url is not set"}';
    die;
}

$login_url = get_login_url().'?oauth=1&redirect='.$redirect_url;

$check_login_response = new AuthenticateResponse($login_url);

echo json_encode($check_login_response);

