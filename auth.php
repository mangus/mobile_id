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
            echo 'Caught exception: ';
            var_dump($e);
        }

        die('todo siin!');
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
            return 'EST';
    }

    public function check_status($sessCode) {
        //küsi autentimisstaatust 
        $response = $this->soapclient->GetMobileAuthenticateStatus((int)$sessCode, false);
        var_dump($response);

    }

    /** Real authentication here */
    function authenticate_with_mobile_id() {
        global $DB, $CFG, $SESSION;
        if ($this->id_card_inserted()) {
            $conditions = array('idnumber' => $this->get_id_number());
            $usertologin = $DB->get_record('user', $conditions, $fields='*');
            if ($usertologin !== false) {
                $USER = complete_user_login($usertologin);
                if (optional_param('password_recovery', false, PARAM_BOOL))
                    $SESSION->wantsurl = $CFG->wwwroot . '/login/change_password.php';
                $goto = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot;
                redirect($goto);
            } else
                $goto = $CFG->wwwroot . '/login/?no_user_with_id=1';
        } else
            $goto = $CFG->wwwroot . '/login/?no_id_card_data=1';
        redirect($goto);
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

