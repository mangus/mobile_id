<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class auth_mobile_id_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $mform->addElement('text', 'mobile_id', get_string('usernameorphone', 'auth_askidnumber'));

        $mform->addRule('mobile_id', get_string('err_whyempty', 'auth_askidnumber'),
            'required', null, 'client');
        $mform->addRule('mobile_id', get_string('err_incorrectid', 'auth_askidnumber'),
            'callback', 'auth_mobile_id_form::valid_username_or_phone', 'server');
        $this->add_action_buttons(false);
    }

    static function valid_username_or_phone($input) {
        //TODO
        return true;
    }    
}
