<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class auth_mobile_id_form extends moodleform {

    // Define the form
    function definition() {
        global $CFG;
        $mform =& $this->_form;
        $mform->addElement('text', 'mobile_id', get_string('usernameorphone', 'auth_mobile_id'));
        $mform->addRule('mobile_id', get_string('err_whyempty', 'auth_mobile_id'),
            'required', null, 'client');
        $mform->addRule('mobile_id', get_string('err_incorrect', 'auth_mobile_id'),
            'callback', 'auth_mobile_id_form::valid_username_or_phone', 'server');
        $this->add_action_buttons(false, get_string('begin_mobile_id_login', 'auth_mobile_id'));
    }

    static function get_user_id($input) {
        global $DB;
        $input = trim($input);
        $userid = null;

        if ((is_numeric($input[0]) or '+' == $input[0]) and strlen($input) >= 7) { // Phone number

            // In Moodle the phone number may be without '+372', so we remove this to still find the right user
            $phone = auth_mobile_id_form::phone_without_code($input);

            // Check if there is a user with that phone number in database...
            $values = array('+372' . $phone, '00372' . $phone, $phone);
            list($in, $params) = $DB->get_in_or_equal($values);
            $condition = 'phone2 ' . $in;
            if ($DB->count_records_select('user', $condition, $params) > 1)
                redirect('/auth/mobile_id/login.php?multiplenumbers=1');
            $userid = $DB->get_field_select('user', 'id', $condition, $params);

        } else { // Moodle username

            // Check if there is such username in Moodle
            $condition = array('username' => $input);
            $userid = $DB->get_field('user', 'id', $condition);
         
        }
        return $userid;
    }

    // Only works with Estonian access code!
    static function phone_without_code($phonenumber) {
        if (substr($phonenumber, 0, 5) == '00372')
            $phonenumber = substr($phonenumber, 5);
        if (substr($phonenumber, 0, 4) == '+372')
            $phonenumber = substr($phonenumber, 4);
        return trim($phonenumber);
    }

    static function valid_username_or_phone($input) {
        return is_numeric(auth_mobile_id_form::get_user_id($input));
    }
}
