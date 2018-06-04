<?php

if (!file_exists('../../config.php')) {
    header('Location: ../../install.php');
    die;
}

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

// required JSON headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

//$pic_size = array('large' => 'f1', 'small' => 'f2');
//
//$pic_src = get_file_url('3'.'/'.$pic_size['large'].'.jpg', null, 'user');
//
//echo $pic_src;

$user_id = 3;

try {
    echo json_encode(enrol_get_all_users_courses($user_id, true));
} catch (coding_exception $e) {
    echo 'error in enrol_get_all_users_courses('.$user_id.', true)';
}
