<?php
/**
 * @author Mart Mangus
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * Authentication Plugin: Login with Mobile-ID
 *
 */

require_once($CFG->dirroot.'/lib/authlib.php');

class auth_plugin_mobile_id extends auth_plugin_base {

    private $sitename = 'moodle.e-ope.ee';
    private $sitemessage = 'Sisselogimine';
    private $wsdluri = 'https://digidocservice.sk.ee/?wsdl';

    /** Constructor */
    function auth_plugin_mobile_id() {
        $this->authtype = 'mobile_id';
        $this->soapoptions = array(
            'location' => 'https://digidocservice.sk.ee',
            'uri' => 'https://digidocservice.sk.ee/DigiDocService/DigiDocService.wsdl',
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'trace' => true,
            'encoding' => 'utf-8',
            'soap_version' => SOAP_1_2,
            'classmap' => array(
                array('MobileAuthenticateResponse' => 'MobileAuthenticateResponse')
            )
        );
        $this->soapclient = new soapclient($this->wsdluri, $this->soapoptions);
    }

    public function start_authenticate($input) {
        global $DB;

        $conditions = array('id' => auth_mobile_id_form::get_user_id($input));
        $userinfo = $DB->get_record('user', $conditions, 'phone2,lang');

        $userphone = auth_mobile_id_form::phone_without_code($userinfo->phone2); // In case the code is already there
        $userphone = '+372' . $userphone;

        // Remote authenticatin begin...
        try {
            $response = $this->soapclient->MobileAuthenticate(
                null, null, $userphone, auth_plugin_mobile_id::language_map($userinfo->lang),
                $this->sitename, $this->sitemessage, '00100000000100000000',
                'asynchClientServer', null, true, false
            );
        } catch (Exception $e) {
            //echo 'Caught exception: ';
            //var_dump($e);
        }

        //die('todo siin!');
        return '1234'; // control code
    }

    public function get_sess_code() {
        //TODO
        return 'SESSCODE';
    }

    // Pay attention here, when Your Moodle has more languages!
    public static function language_map($twodigit) {
        $map = array(
            'ee' => 'EST',
            'ru' => 'RUS',
            'en' => 'ENG'
        );
        if (array_key_exists($twodigit, $map))
            return $map[$twodigit];
        else
            return 'EST'; // Default language
    }

    public function check_status($sesscode) {
        //küsi autentimisstaatust 
        $response = $this->soapclient->GetMobileAuthenticateStatus((int)$sesscode, false);
        var_dump($response);
    }
    public function can_login($sesscode) {
        //$this->check_status($sesscode);

        //if mobileID auth successful
            return true;
        // else return false
    }

    private function get_phone_number($sesscode) {
        // TODO
        return;
    }
    public function get_control_code($sesscode) {
        //TODO
        return 1234;
    }

    /** Authentication to Moodle here */
    private function login($sesscode) {
        global $DB, $CFG, $SESSION;

        if (!$this->can_login($sesscode))
            throw new Exception('Invalid Mobile-ID login!');

        $userid = auth_mobile_id_form::get_user_id($this->get_phone_number($sesscode));
        $usertologin = $DB->get_record('user', array('id' => $userid), $fields='*');
        if ($usertologin !== false) {
            $USER = complete_user_login($usertologin);
            if (optional_param('password_recovery', false, PARAM_BOOL))
                $SESSION->wantsurl = $CFG->wwwroot . '/login/change_password.php';
            $goto = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot;
            redirect($goto);
        } else
            throw new Exception('Unexpected error in Mobile-ID login');
    }

    /** Login is going through file auth/mobile_id/login.php instead of usual login form */
    function user_login($username, $password) {
        return false;
    }

    /** Creates "login with Mobile-ID" link to Moodle login page */
    /*
    function loginpage_idp_list($wantsurl) {
        global $CFG;
        return array(
            array(
                'url' => new moodle_url($CFG->wwwroot . '/auth/mobile_id/login.php'),
                'icon' => new pix_icon('mobiilid', 'Login with Mobil-ID'),
                    // Need to copy this file (cp auth/est_id_card/images/idkaart.gif pix/)
                'name' => get_string('login_with', 'auth_mobile_id')
            )
        );
    }
    */

}

