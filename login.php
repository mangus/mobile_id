<?php

require_once('../../config.php');
require_once('auth.php');
//require_once('insertidnumber_form.php');

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_url("$CFG->httpswwwroot/auth/mobile_id/login.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$PAGE->navbar->add(get_string('loginwithmobileid', 'auth_mobile_id'));
$PAGE->set_heading(get_string('insertnameor', 'auth_mobile_id'));

// Form...
$form = new auth_mobile_id_form();
if ($fromform=$form->get_data())
{
    print_r($fromform);
    /* TODO
    $goto = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot;
    redirect($goto);    
    */
}
else
    $form->set_data($fromform);

// Outuput start...
echo $OUTPUT->header();
echo $OUTPUT->box(get_string('insertnameor', 'auth_mobile_id'));
$form->display();
echo $OUTPUT->footer();
