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

    private $sitename = 'moodle.hitsa.ee';
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
            'soap_version' => SOAP_1_1,
            'classmap' => array(
                array('MobileAuthenticateResponse' => 'MobileAuthenticateResponse')
            )
        );
        $this->soapclient = new soapclient($this->wsdluri, $this->soapoptions);
    }

    /** Starts Mobile-ID authentication session with DigiDocService */
    public function start_authenticate($input) {
        global $DB;

        $conditions = array('id' => (int) auth_mobile_id_form::get_user_id($input));
        $userinfo = $DB->get_record('user', $conditions, 'id,phone2,lang,idnumber');

        if (!empty($userinfo->phone2)) {
            $userphone = auth_mobile_id_form::phone_without_code($userinfo->phone2); // In case the code is already there
            $userphone = '+372' . $userphone;
        } else if (empty($userinfo->idnumber)) {
            redirect('/auth/mobile_id/login.php?nouserdata=1');
        } else
            $userphone = null;

        try {
            $response = $this->soapclient->MobileAuthenticate(
                $userinfo->idnumber, 'EE', $userphone, $this->language_map($userinfo->lang),
                $this->sitename, $this->sitemessage, $this->get_sp_challenge(),
                'asynchClientServer', null, true, false
            );
        } catch (Exception $e) {
            throw new Exception('Mobile-ID error: ' . $e->getMessage());
        }

        // Keeping a recond in database
        $record = new stdClass();
        $record->sesscode = $response['Sesscode'];
        $record->userid = $userinfo->id;
        $record->controlcode = $response['ChallengeID'];
        $record->status = $response['Status'];
        $record->starttime = time();
        $DB->insert_record('mobile_id_login', $record);

        return $response['Sesscode'];
    }

    /**
     * Mapping Moodle language to Mobile-ID language
     * Pay attention here, when Your Moodle has more languages!
     */
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

    public function status_okay($sesscode) {
        global $DB;
        $isok = $DB->get_record('mobile_id_login', array('sesscode' => (int) $sesscode), 'status');
        return $isok->status == 'OK';
    }

    public function can_login($sesscode) {
        $status = $this->get_status((int) $sesscode);
        return 'USER_AUTHENTICATED' == $status ? true : false;
    }

    /** Checks status from DigiDocService and updates plugin's database entry for current login */
    public function update_status($sesscode) {
        global $DB;

        $response = $this->soapclient->GetMobileAuthenticateStatus((int) $sesscode, false);
    	$dbid = $DB->get_record('mobile_id_login', array('sesscode' => (int) $sesscode), 'id');

        $record = new stdClass();
        $record->id = $dbid->id;
        $record->sesscode = (int) $sesscode;
        $record->status = $response['Status'];
        $DB->update_record('mobile_id_login', $record, false);        
    }

    public function get_status($sesscode) {
        global $DB;
        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => (int) $sesscode), 'status');
        return $mobileid->status;
    }

    public function get_control_code($sesscode) {
        global $DB;
        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => (int) $sesscode), 'controlcode');
        return $mobileid->controlcode;
    }

    /** Deletes current login entry from database */
    private function clean_login($userid) {
        global $DB;
        $DB->delete_records('mobile_id_login', array('userid' => $userid));
    }

    /** Deletes used (unactive) sessions from database */
    private function clean_old_logins() {
        global $DB;
        $DB->delete_records_select('mobile_id_login', 'starttime < ?', array(time()-120 /* 2 minutes */));
    }

    public function get_sp_challenge() {
        $tenbytes = '';
        for ($i = 0; $i < 10; $i++) {
            $byte = base_convert(mt_rand(0, 255), 10, 16);
            if (strlen($byte) < 2)
                $byte = '0' . $byte;
            $tenbytes .= $byte;
        }
        return $tenbytes;
    }

    /** Authentication to Moodle here */
    public function login($sesscode) {
        global $DB, $CFG, $SESSION;

        if (!$this->can_login((int) $sesscode))
            throw new Exception('Invalid Mobile-ID login!');

        $mobileid = $DB->get_record('mobile_id_login', array('sesscode' => (int) $sesscode), 'userid');
        $usertologin = get_complete_user_data('id', $mobileid->userid);
        if ($usertologin !== false) {

            // This block is e-Learning Development Center specific "hack",
            // for forcing users to insert their ID-number.
            // You propably need to delete this
            require_once($CFG->dirroot.'/auth/askidnumber/auth.php');
            $ask = new auth_plugin_askidnumber();
            $ask->user_authenticated_hook($usertologin, '', '');
            // End of this block

            $USER = complete_user_login($usertologin);
            $this->clean_login($mobileid->userid);
            $this->clean_old_logins();
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
                    // Need to copy this file (cp auth/mobile_id/images/mobiilid.gif pix/)
                'name' => get_string('login_with', 'auth_mobile_id')
            )
        );
    }
    */

}

