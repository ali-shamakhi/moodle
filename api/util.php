<?php

function get_userid_accessdomain_access_by_token(moodle_database $DB, int $current_timestamp, int $token_valid_seconds, string $access_token = null) {
    if ($access_token === null || trim($access_token) == "") return null;
    try {
        $auth_record = $DB->get_record('auth', array('token'=>$access_token), '*', MUST_EXIST);
        if ($current_timestamp - $auth_record->accesseddate >= $token_valid_seconds) {
            $DB->delete_records('auth', array('id'=>$auth_record->id));
            return null;
        }
        $auth_record_new = new stdClass;
        $auth_record_new->id = $auth_record->id;
        $auth_record_new->accesseddate = $current_timestamp;
        $DB->update_record('auth', $auth_record_new);
        $userid_accessdomain = new stdClass;
        $userid_accessdomain->userid = $auth_record->userid;
        $userid_accessdomain->accessdomain = $auth_record->accessdomain;
        return $userid_accessdomain;
    } catch (dml_exception $dml_ex) {
        // no matched token in DB
        return null;
    }
}

function get_access_domain_valid_seconds(string $access_domain) {
    switch ($access_domain) {
        case 'attendance': return (int)(6 * 60 * 60);
        default: return 0;
    }
}