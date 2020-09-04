<?php

require_once('../../config.php');
require_once('vendor/autoload.php');
require_once('auth.php');
require_once('mobile_id_form.php');

// Login steps
define('INSERT_NAME_OR_PHONE', 1);
define('WAIT_FOR_PIN1', 2);
define('MOBILE_ID_ERROR', 9);

$context = context_system::instance();

$PAGE->set_url("$CFG->httpswwwroot/auth/mobile_id/login.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$PAGE->navbar->add(get_string('loginwithmobileid', 'auth_mobile_id'));
$PAGE->set_heading(get_string('loginwithmobileid', 'auth_mobile_id'));

$form = new auth_mobile_id_form();
$login = new auth_plugin_mobile_id();

$step = INSERT_NAME_OR_PHONE;

$loginsessionid = optional_param('startlogin', false, PARAM_TEXT);
$waitsessionid = optional_param('waitmore', false, PARAM_NUMBER);
$timeout = optional_param('timeout', false, PARAM_BOOL);
$error = optional_param('error', false, PARAM_BOOL);
$nouserdata = optional_param('nouserdata', false, PARAM_BOOL);
$multiplenumbers = optional_param('multiplenumbers', false, PARAM_BOOL);

$vars = new stdClass();
$vars->loginsessionid = $loginsessionid;
$vars->waitsessionid = $waitsessionid;
$vars->error = $error;
$vars->nouserdata = $nouserdata;
$vars->multiplenumbers = $multiplenumbers;
$vars->userid = $USER->id;

// Start Mobile-ID login...

if ($USER->id && $USER->id != 1) { // User already logged in
    redirect('/login/');

} else if ($loginsessionid) { // Mobile-ID autentication successful
    $login->login($loginsessionid);

} else if ($error or $timeout or $nouserdata or $multiplenumbers) {
    $step = MOBILE_ID_ERROR;

} else if ($waitsessionid) { // After no Javascript status check
    $step = WAIT_FOR_PIN1;
    $sessionid = $waitsessionid;
    $controlcode = $login->get_control_code($sessionid);

} else if ($fromform = $form->get_data()) {
    // Start Mobile-ID login...
    $step = WAIT_FOR_PIN1;
    $PAGE->requires->js('/auth/mobile_id/status_update.js');
    try {
        $sessionid = $login->start_authenticate($fromform->mobile_id, $fromform->nationalIdentityNumber);
        $controlcode = $login->get_control_code($sessionid);
        // Now start checking status with AJAX...
    } catch (\Exception $e) {
        $step = MOBILE_ID_ERROR;
        $error = $e->getMessage();
    }
} else {
    $form->set_data($fromform);
}

// Outuput start...
echo $OUTPUT->header();

switch ($step) {

    case INSERT_NAME_OR_PHONE:
        echo $OUTPUT->box(get_string('insertnameor', 'auth_mobile_id'));
        $form->display();
        break;

    case WAIT_FOR_PIN1:
        echo $OUTPUT->box_start();
        echo get_string('check_control_code', 'auth_mobile_id') . '<strong>' . $controlcode . '</strong><br />';
        echo get_string('then_insert_pin1', 'auth_mobile_id') . '<br />';
        echo get_string('waiting_for_mobile_id', 'auth_mobile_id');
        echo "<span id=\"hideWithJavascript\">... <a href=\"/auth/mobile_id/ajax.php?noajax=1&sessionid=$sessionid\">"
            . get_string('manual_update', 'auth_mobile_id') . "</a></span>";
        echo $OUTPUT->box_end();
        echo "<div style=\"display: none;\" id=\"sessionid\">$sessionid</div>";
        break;

    case MOBILE_ID_ERROR:
        if ($timeout)
            echo $OUTPUT->box(get_string('timeout', 'auth_mobile_id'));
        else if ($error)
            echo $OUTPUT->box(get_string('error', 'auth_mobile_id'));
        else if ($nouserdata)
            echo $OUTPUT->box(get_string('no_user_data', 'auth_mobile_id'));
        else if ($multiplenumbers)
            echo $OUTPUT->box(get_string('multiple_numbers', 'auth_mobile_id'));

        echo "<div><a href=\"/auth/mobile_id/login.php\">"
            . get_string('try_again', 'auth_mobile_id') . "</a></div>";
        break;

    default:
        throw new Exception('Undefined Mobile-ID login step');
}

echo $OUTPUT->footer();
