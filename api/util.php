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

function get_weekday_from_day_name($day_name) {
    if ($day_name == null) return 0;
    $day_name = strtolower($day_name);
    switch ($day_name) {
        case 'mo':
        case 'mon':
        case 'monday':
        return 1;
        case 'tu':
        case 'tue':
        case 'tuesday':
        return 2;
        case 'we':
        case 'wed':
        case 'wednesday':
        return 3;
        case 'th':
        case 'thu':
        case 'thursday':
        return 4;
        case 'fr':
        case 'fri':
        case 'friday':
        return 5;
        case 'sa':
        case 'sat':
        case 'saturday':
        return 6;
        case 'su':
        case 'sun':
        case 'sunday':
        return 7;
        default:
            return 0;
    }
}

const MONDAY = 1;
const TUESDAY = 2;
const WEDNESDAY = 3;
const THURSDAY = 4;
const FRIDAY = 5;
const SATURDAY = 6;
const SUNDAY = 7;

function get_course_session_timestamps($course_summary, int $current_timestamp) {
    $local_timestamp = $current_timestamp + date('Z');
//    error_log('cur: '.$current_timestamp);
//    error_log('loc: '.$local_timestamp);
    $session_start_timestamp = $current_timestamp - ($current_timestamp % 86400) + (7 * 60 * 60) - date('Z');
    $session_end_timestamp = $current_timestamp - ($current_timestamp % 86400) + (19 * 60 * 60) - date('Z');
    $current_weekday = date('N', $local_timestamp);
    preg_match_all('/<p>(.*?)<\/p>/', $course_summary, $summary_paragraphs, PREG_PATTERN_ORDER);
    $timetable_started = false;
    foreach ($summary_paragraphs[1] as $summary_line) {
        $line = strtolower(str_replace('&nbsp;', '', str_replace(' ', '', $summary_line)));
        if ($line == '[timetable]') {
            if ($timetable_started) {
                break;
            }
            $timetable_started = true;
            continue;
        }
        if ($line == '[/timetable]') {
            break;
        }
        if ($timetable_started) {
            preg_match_all('/([a-z]*)([0-9]{1,2}):([0-9]{2})-([0-9]{1,2}):([0-9]{2})/', $line, $time_parts, PREG_PATTERN_ORDER);
            $weekday = get_weekday_from_day_name($time_parts[1][0]);
            if ($weekday == $current_weekday) {
                $session_start_timestamp = $current_timestamp - ($current_timestamp % 86400) + ((int)$time_parts[2][0]) * 60 * 60 + ((int)$time_parts[3][0]) * 60 - date('Z');
                $session_end_timestamp = $current_timestamp - ($current_timestamp % 86400) + ((int)$time_parts[4][0]) * 60 * 60 + ((int)$time_parts[5][0]) * 60 - date('Z');
                break;
            }
        }
    }
    $session_timestamps = new stdClass;
    $session_timestamps->start = $session_start_timestamp;
    $session_timestamps->end = $session_end_timestamp;
    $session_timestamps->local_date_string = date('Y F j', $local_timestamp);
    return $session_timestamps;
}

function get_attendance_assign_data(int $course_id, int $attendance_category_id, int $session_start_timestamp, int $session_end_timestamp, string $local_date_string) {
    $assign_data = new stdClass;
    $assign_data->coursemodule = 0;
    $assign_data->section = 0;
    $assign_data->module = 1;  // assign
    $assign_data->modulename = 'assign';
    $assign_data->instance = 0;
    $assign_data->add = 'assign';
    $assign_data->update = 0;
    $assign_data->return = 0;
    $assign_data->sr = 0;
    $assign_data->groupmode = 0;
    $assign_data->availabilityconditionsjson = '{"op":"&","c":[],"showc":[]}';
    $assign_data->name = 'Attendance '.$local_date_string;
    $assign_data->course = $course_id;
    $assign_data->intro = '';                  // HIDDEN!
    $assign_data->introformat = FORMAT_HTML;   // HIDDEN!
    $assign_data->showdescription = 0;
    $assign_data->alwaysshowdescription = 1;
    $assign_data->nosubmissions = 1;           // HIDDEN!
    $assign_data->assignsubmission_comments_enabled = 1;
    $assign_data->assignfeedback_comments_enabled = 1;
    $assign_data->assignfeedback_comments_commentinline = 0;
    $assign_data->submissiondrafts = 0;
    $assign_data->requiresubmissionstatement = 0;
    $assign_data->attemptreopenmethod = 'none';
    $assign_data->sendnotifications = 0;
    $assign_data->sendlatenotifications = 0;
    $assign_data->sendstudentnotifications = 1;
    $assign_data->allowsubmissionsfromdate = $session_start_timestamp;
    $assign_data->duedate = $session_end_timestamp;
    $assign_data->cutoffdate = 0;
    $assign_data->gradingduedate = 0;
    $assign_data->grade = 1;
    $assign_data->advancedgradingmethod_submissions = '';
    $assign_data->gradecat = $attendance_category_id;
    $assign_data->gradepass = 1.0;
    $assign_data->completionunlocked = 1;
    $assign_data->completionsubmit = 1;
    $assign_data->completion = 0;
    $assign_data->completionexpected = 0;
    $assign_data->tags = array();
    $assign_data->teamsubmission = 0;
    $assign_data->preventsubmissionnotingroup = 0;
    $assign_data->requireallteammemberssubmit = 0;
    $assign_data->blindmarking = 0;
    $assign_data->revealidentities = 0;        // HIDDEN!
    $assign_data->maxattempts = -1;
    $assign_data->markingworkflow = 0;
    $assign_data->markingallocation = 0;
    $assign_data->visible = 1;
    $assign_data->visibleoncoursepage = 1;
    $assign_data->cmidnumber = '';
    $assign_data->competency_rule = 0;
    return $assign_data;
}
