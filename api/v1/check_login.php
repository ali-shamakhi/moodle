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
 *   path="/check_login.php",
 *   summary="Verifies that user is logged in.",
 *   consumes={"application/json"},
 *   produces={"application/json"},
 *   tags={"Login"},
 *   parameters={},
 *   @SWG\Parameter(
 *      name="params",
 *      in="query",
 *      type="string",
 *      format="application/json",
 *      default=
"
{
""redirect_url"":""{DOMAIN}/attendance/""
}
",
 *      @SWG\Schema(
 *         ref="#/definitions/CheckLoginParameter"
 *      )
 *   ),
 *   @SWG\Response(
 *      response=200,
 *      description="Authentication Details",
 *      @SWG\Schema(
 *          ref="#/definitions/CheckLoginResponse"
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="Error",
 *      examples={
"
{
""error"":""redirect_url is not set""
}
"
},
 *   )
 * )
 */

$check_login_parameter = json_decode($_GET["params"]);
try {
    $redirect_url = $check_login_parameter->redirect_url;
} catch (exception $e) {
    echo '{"error": "redirect_url is not set"}';
    die;
}

$logged_in = false;
if ($USER !== null && $USER->id != 0) {
    $logged_in = true;
}

$login_url = get_login_url().'?redirect='.$redirect_url;

$check_login_response = new CheckLoginResponse($logged_in, $login_url);

echo json_encode($check_login_response);

?>
