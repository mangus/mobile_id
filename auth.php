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

        $conditions = array('id' => (int) auth_mobile_id_form::get_user_id($input));
        $userinfo = $DB->get_record('user', $conditions, 'id,phone2,lang,idnumber');

        if (!empty($userinfo->phone2)) {
            $userphone = auth_mobile_id_form::phone_without_code($userinfo->phone2); // In case the code is already there
            $userphone = '+372' . $userphone;
        } else
            $userphone = null;

        // Remote authenticatin begin...
        /*
        try {
            $response = $this->soapclient->MobileAuthenticate(
                $userinfo->idnumber, 'EE', $userphone, $this->language_map($userinfo->lang),
                $this->sitename, $this->sitemessage, '00100000000100000000',
                'asynchClientServer', null, true, false
            );
        } catch (Exception $e) {
            echo 'Caught exception: ';
            var_dump($e);
        }
        var_dump($response);
        die('todo siin!');
        */

        // Keeping a recond in database
        $record = new stdClass();
        $record->sesscode = 123456789; //response_sesscode_here
        $record->userid = $userinfo->id;
        $record->controlcode = 1234; // response_controlcode_here
        $record->status = 'OK'; // from response
        $record->timemodified = time();
        $DB->insert_record('mobile_id_login', $record);

        return 123456789; // response_sesscode_here
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

    public function update_status($sesscode) {
        global $DB;
        /* TODO
        $response = $this->soapclient->GetMobileAuthenticateStatus($sesscode, false);
        var_dump($response);
        die('todo here');
        */
	$dbid = $DB->get_record('mobile_id_login', array('sesscode' => $sesscode), 'id');

        $record = new stdClass();
        $record->id = $dbid->id;
        $record->sesscode = $sesscode;
        $record->status = 'USER_AUTHENTICATED';
        $DB->update_record('mobile_id_login', $record, false);        
    }
    public function can_login($sesscode) {
        global $DB;
        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => $sesscode), 'status');
        if (!empty($mobileid) && $mobileid->status == 'USER_AUTHENTICATED')
            return true;
        else
            return false;
    }

    public function get_control_code($sesscode) {
        global $DB;
        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => $sesscode), 'controlcode');
        return $mobileid->controlcode;
    }

    /** Authentication to Moodle here */
    public function login($sesscode) {
        global $DB, $CFG, $SESSION;

        if (!$this->can_login($sesscode))
            throw new Exception('Invalid Mobile-ID login!');

        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => $sesscode), 'userid');
        $usertologin = $DB->get_record('user', array('id' => $mobileid->userid), $fields='*');
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

