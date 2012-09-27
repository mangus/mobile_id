<?php

require_once('../../config.php');
require_once('auth.php');
require_once('mobile_id_form.php');

// Login steps
define('INSER_NAME_OR_PHONE', 1);
define('WAIT_FOR_PIN1', 2);

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_url("$CFG->httpswwwroot/auth/mobile_id/login.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$PAGE->navbar->add(get_string('loginwithmobileid', 'auth_mobile_id'));
$PAGE->set_heading(get_string('loginwithmobileid', 'auth_mobile_id'));

$form = new auth_mobile_id_form();
$login = new auth_plugin_mobile_id();

$step = INSER_NAME_OR_PHONE;

$sesscode = optional_param('startlogin', false, PARAM_ALPHANUM);
if ($sesscode) { // Mobile-ID autentication successful
    $login->login($sesscode);
} else if ($fromform = $form->get_data()) {
    // Start Mobile-ID login...
    $step = WAIT_FOR_PIN1;
    $PAGE->requires->js('/auth/mobile_id/status_update.js');
    $login->start_authenticate($fromform->mobile_id);
    // Now start checking status with AJAX...
} else
    $form->set_data($fromform);

// Outuput start...
echo $OUTPUT->header();

switch ($step) {

    case INSER_NAME_OR_PHONE:
        echo $OUTPUT->box(get_string('insertnameor', 'auth_mobile_id'));
        $form->display();
        break;

    case WAIT_FOR_PIN1:
        echo $OUTPUT->box_start();
        echo get_string('waiting_for_pin1', 'auth_mobile_id');
        echo "<div id=\"hideWithJavascript\"><a href=\"/auth/mobile_id/ajax.php?noajax=1&sesscode=sesscodehere\">"
            . get_string('manual_update', 'auth_mobile_id') . "</a></div>";
        echo $OUTPUT->box_end();
        break;

    default:
        throw new Exception('Undefined Mobile-ID login step');
}

echo $OUTPUT->footer();

