<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main login page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once('lib.php');

redirect_if_major_upgrade_required();

$testsession = optional_param('testsession', 0, PARAM_INT); // test session works properly
$anchor      = optional_param('anchor', '', PARAM_RAW);      // Used to restore hash anchor to wantsurl.

$context = context_system::instance();
$PAGE->set_url("$CFG->wwwroot/login/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

/// Initialize variables
$errormsg = '';
$errorcode = 0;

// custom redirect
try {
    $redirect_url = $_GET["redirect"];
    if (isset($redirect_url) && trim($redirect_url) != "")
        $SESSION->redirect_url = $redirect_url;
} catch (exception $e) {
    unset($SESSION->redirect_url);
}
//error_log('RRR: redirect_url='.$redirect_url);
//$oauth = 0;
//try {
//    $oauth = intval($_GET["oauth"]);
//} catch (exception $e) {
//    // nothing
//}

if (/*$oauth && */isset($USER->id) && $USER->id != 0 && isset($SESSION->redirect_url)) {
    $redirect_url = $SESSION->redirect_url;
    unset($SESSION->redirect_url);
    error_log('GAT_HERE');
    $access_token = get_access_token($DB, $USER, 'attendance');
    $url = $redirect_url.'?token='.$access_token;
    $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
    $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />', FORMAT_HTML));
    @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
    @header('Location: '.$url);
    echo bootstrap_renderer::plain_redirect_message($encodedurl);
    exit;
}

// login page requested session test
if ($testsession) {
    if ($testsession == $USER->id) {
        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot.'/';
        }
        unset($SESSION->wantsurl);
        error_log('RD1: urltogo='.$urltogo);
        if (isset($USER->id) && $USER->id != 0 && isset($SESSION->redirect_url)) {
            error_log('GET_THERE. userid='.$USER->id);
            $redirect_url = $SESSION->redirect_url;
            unset($SESSION->redirect_url);
            $access_token = get_access_token($DB, $USER, 'attendance');
            $url = $redirect_url.'?token='.$access_token;
            $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
            $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />', FORMAT_HTML));
            @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
            @header('Location: '.$url);
            echo bootstrap_renderer::plain_redirect_message($encodedurl);
            exit;
        }
        else {
            error_log('GGG: userid='.$USER->id);
            redirect($urltogo);
        }
    } else {
        // TODO: try to find out what is the exact reason why sessions do not work
        $errormsg = get_string("cookiesnotenabled");
        $errorcode = 1;
    }
}

/// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

/// auth plugins may override these - SSO anyone?
$frm  = false;
$user = false;

$authsequence = get_enabled_auth_plugins(true); // auths, in sequence
foreach($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->loginpage_hook();
}


/// Define variables used in page
$site = get_site();

// Ignore any active pages in the navigation/settings.
// We do this because there won't be an active page there, and by ignoring the active pages the
// navigation and settings won't be initialised unless something else needs them.
$PAGE->navbar->ignore_active();
$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

if ($user !== false or $frm !== false or $errormsg !== '') {
    // some auth plugin already supplied full user, fake form data or prevented user login with error message

} else if (!empty($SESSION->wantsurl) && file_exists($CFG->dirroot.'/login/weblinkauth.php')) {
    // Handles the case of another Moodle site linking into a page on this site
    //TODO: move weblink into own auth plugin
    include($CFG->dirroot.'/login/weblinkauth.php');
    if (function_exists('weblink_auth')) {
        $user = weblink_auth($SESSION->wantsurl);
    }
    if ($user) {
        $frm->username = $user->username;
    } else {
        $frm = data_submitted();
    }

} else {
    $frm = data_submitted();
}

// Restore the #anchor to the original wantsurl. Note that this
// will only work for internal auth plugins, SSO plugins such as
// SAML / CAS / OIDC will have to handle this correctly directly.
if ($anchor && isset($SESSION->wantsurl) && strpos($SESSION->wantsurl, '#') === false) {
    $wantsurl = new moodle_url($SESSION->wantsurl);
    $wantsurl->set_anchor(substr($anchor, 1));
    $SESSION->wantsurl = $wantsurl->out();
}

/// Check if the user has actually submitted login data to us

if ($frm and isset($frm->username)) {                             // Login WITH cookies

    $frm->username = trim(core_text::strtolower($frm->username));

    if (is_enabled_auth('none') ) {
        if ($frm->username !== core_user::clean_field($frm->username, 'username')) {
            $errormsg = get_string('username').': '.get_string("invalidusername");
            $errorcode = 2;
            $user = null;
        }
    }

    if ($user) {
        //user already supplied by aut plugin prelogin hook
    } else if (($frm->username == 'guest') and empty($CFG->guestloginbutton)) {
        $user = false;    /// Can't log in as guest if guest button is disabled
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $user = authenticate_user_login($frm->username, $frm->password, false, $errorcode);
        }
    }

    // Intercept 'restored' users to provide them with info & reset password
    if (!$user and $frm and is_restored_user($frm->username)) {
        $PAGE->set_title(get_string('restoredaccount'));
        $PAGE->set_heading($site->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('restoredaccount'));
        echo $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
        require_once('restored_password_form.php'); // Use our "supplanter" login_forgot_password_form. MDL-20846
        $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }

    if ($user) {

        // language setup
        if (isguestuser($user)) {
            // no predefined language for guests - use existing session or default site lang
            unset($user->lang);

        } else if (!empty($user->lang)) {
            // unset previous session language - use user preference instead
            unset($SESSION->lang);
        }

        if (empty($user->confirmed)) {       // This account was never confirmed
            $PAGE->set_title(get_string("mustconfirm"));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            echo $OUTPUT->box(get_string("emailconfirmsent", "", $user->email), "generalbox boxaligncenter");
            echo $OUTPUT->footer();
            die;
        }

    /// Let's get them all set up.
        complete_user_login($user);

        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        // sets the username cookie
        if (!empty($CFG->nolastloggedin)) {
            // do not store last logged in user in cookie
            // auth plugins can temporarily override this from loginpage_hook()
            // do not save $CFG->nolastloggedin in database!

        } else if (empty($CFG->rememberusername) or ($CFG->rememberusername == 2 and empty($frm->rememberusername))) {
            // no permanent cookies, delete old one if exists
            set_moodle_cookie('');

        } else {
            set_moodle_cookie($USER->username);
        }

        $urltogo = core_login_get_return_url();

    /// check if user password has expired
    /// Currently supported only for ldap-authentication module
        $userauth = get_auth_plugin($USER->auth);
        if (!isguestuser() and !empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
            $externalchangepassword = false;
            if ($userauth->can_change_password()) {
                $passwordchangeurl = $userauth->change_password_url();
                if (!$passwordchangeurl) {
                    $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
                } else {
                    $externalchangepassword = true;
                }
            } else {
                $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
            }
            $days2expire = $userauth->password_expire($USER->username);
            $PAGE->set_title("$site->fullname: $loginsite");
            $PAGE->set_heading("$site->fullname");
            if (intval($days2expire) > 0 && intval($days2expire) < intval($userauth->config->expiration_warning)) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordwillexpire', 'auth', $days2expire), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            } elseif (intval($days2expire) < 0 ) {
                if ($externalchangepassword) {
                    // We end the session if the change password form is external. This prevents access to the site
                    // until the password is correctly changed.
                    require_logout();
                } else {
                    // If we use the standard change password form, this user preference will be reset when the password
                    // is changed. Until then it will prevent access to the site.
                    set_user_preference('auth_forcepasswordchange', 1, $USER);
                }
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordisexpired', 'auth'), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            }
        }

        // Discard any errors before the last redirect.
        unset($SESSION->loginerrormsg);

        // test the session actually works by redirecting to self
        $SESSION->wantsurl = $urltogo;
        error_log('RD2');
        if (isset($SESSION->redirect_url)) {
            $url_params = array('testsession'=>$USER->id, 'oauth'=>1);  // oauth not needed
        } else {
            $url_params = array('testsession'=>$USER->id);
        }
        redirect(new moodle_url(get_login_url(), $url_params));

    } else {
        if (empty($errormsg)) {
            if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
                $errormsg = get_string("unauthorisedlogin", "", $frm->username);
            } else {
                $errormsg = get_string("invalidlogin");
                $errorcode = 3;
            }
        }
    }
}

/// Detect problems with timedout sessions
if ($session_has_timed_out and !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

/// First, let's remember where the user was trying to get to before they got here

if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = null;
    $referer = null;
    if (isset($SESSION->redirect_url)) {
        $referer = $SESSION->redirect_url;
    } else {
        $referer = get_local_referer(false);
    }
    if ($referer &&
            $referer != $CFG->wwwroot &&
            $referer != $CFG->wwwroot . '/' &&
            $referer != $CFG->wwwroot . '/login/' &&
            strpos($referer, $CFG->wwwroot . '/login/?') !== 0 &&
            strpos($referer, $CFG->wwwroot . '/login/index.php') !== 0) { // There might be some extra params such as ?lang=.
        $SESSION->wantsurl = $referer;
    }
}

/// Redirect to alternative login URL if needed
if (!empty($CFG->alternateloginurl)) {
    $loginurl = new moodle_url($CFG->alternateloginurl);

    $loginurlstr = $loginurl->out(false);

    if (strpos($SESSION->wantsurl, $loginurlstr) === 0) {
        // We do not want to return to alternate url.
        $SESSION->wantsurl = null;
    }

    // If error code then add that to url.
    if ($errorcode) {
        $loginurl->param('errorcode', $errorcode);
    }

    error_log('RD3');
    redirect($loginurl->out(false));
}

/// Generate the login page with forms

if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username) && $authsequence[0] != 'shibboleth') {  // See bug 5184
    if (!empty($_GET["username"])) {
        // we do not want data from _POST here
        $frm->username = clean_param($_GET["username"], PARAM_RAW); // we do not want data from _POST here
    } else {
        $frm->username = get_moodle_cookie();
    }

    $frm->password = "";
}

if (!empty($SESSION->loginerrormsg)) {
    // We had some errors before redirect, show them now.
    $errormsg = $SESSION->loginerrormsg;
    unset($SESSION->loginerrormsg);

} else if ($testsession) {
    // No need to redirect here.
    unset($SESSION->loginerrormsg);

} else if ($errormsg or !empty($frm->password)) {
    // We must redirect after every password submission.
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }
    error_log('RD4');
    redirect(new moodle_url('/login/index.php'));
}

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();

if (isloggedin() and !isguestuser()) {
    // prevent logging when already logged in, we do not want them to relogin by accident because sesskey would be changed
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url('/login/logout.php', array('sesskey'=>sesskey(),'loginpage'=>1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
} else {
    $loginform = new \core_auth\output\login($authsequence, $frm->username);
    $loginform->set_error($errormsg);
    echo $OUTPUT->render($loginform);
}

echo $OUTPUT->footer();
