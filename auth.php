<?php
/**
 * @author Mart Mangus
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * Authentication Plugin: Login with Mobile-ID
 *
 */

require_once($CFG->dirroot . '/lib/authlib.php');
require_once('vendor/autoload.php');

use Sk\Mid\DisplayTextFormat;
use Sk\Mid\Language\ENG;
use Sk\Mid\Language\EST;
use Sk\Mid\Language\LIT;
use Sk\Mid\Language\RUS;
use Sk\Mid\MobileIdAuthenticationHashToSign;
use Sk\Mid\MobileIdClient;
use Sk\Mid\Rest\Dao\Request\AuthenticationRequest;


class auth_plugin_mobile_id extends auth_plugin_base
{

    private $midRestClient;

    function __construct()
    {
        global $CFG;

        $this->authtype = 'mobile_id';

        $relyingPartyUUID = $CFG->relyingPartyUUID;
        $relyingPartyName = $CFG->relyingPartyName;
        $hostUrl = $CFG->hostUrl;

        $this->midRestClient = MobileIdClient::newBuilder()
            ->withRelyingPartyUUID($relyingPartyUUID)
            ->withRelyingPartyName($relyingPartyName)
            ->withHostUrl($hostUrl)
            ->withLongPollingTimeoutSeconds(60)
            ->withPollingSleepTimeoutSeconds(5)
            ->build();
    }

    /** Starts Mobile-ID authentication */
    public function start_authenticate($input, $nationalIdentityNumber)
    {
        global $CFG, $DB;

        $conditions = array(
            'id' => auth_mobile_id_form::get_user_id($input),
            'idnumber' => $nationalIdentityNumber);
        $userinfo = $DB->get_record('user', $conditions, 'id,phone2,lang,idnumber');

        $phoneNumber = null;
        if (!empty($userinfo->phone2)) {
            $phoneNumber = auth_mobile_id_form::phone_without_code($userinfo->phone2); // In case the code is already there
            $phoneNumber = '+372' . $phoneNumber;
        } else if (empty($userinfo->idnumber)) {
            redirect('/auth/mobile_id/login.php?nouserdata=1');
        }

        $authenticationHash = MobileIdAuthenticationHashToSign::generateRandomHashOfDefaultType();
        $verificationCode = $authenticationHash->calculateVerificationCode();

        //sleep so in case when user logs in from smart device, he/she can see the verification code on the webpage before the smart id application comes up
        sleep(2);

        $request = AuthenticationRequest::newBuilder()
            ->withPhoneNumber($phoneNumber)
            ->withNationalIdentityNumber($nationalIdentityNumber)
            ->withHashToSign($authenticationHash)
            ->withLanguage(self::language_map(current_language()))
            ->withDisplayText(get_string('mobile_display_text', 'auth_mobile_id'))
            ->withDisplayTextFormat(DisplayTextFormat::GSM7)
            ->build();

        try {
            $response = $this->midRestClient->getMobileIdConnector()->initAuthentication($request); // this doesn't throw exception
            $sessID = $response->getSessionID();
            // Keeping a recond in database
            $record = new stdClass();
            $record->sessionid = $sessID;
            $record->userid = $userinfo->id;
            $record->controlcode = $verificationCode;
            $record->hash = $authenticationHash->getHashInBase64();
            $record->status = 'OUTSTANDING_TRANSACTION';
            $record->starttime = time();
            $dbResult = $DB->insert_record('mobile_id_login', $record);
            return $sessID;
        } catch (\Exception $e) {
            throw new \Exception('Mobile-ID error: ' . $e->getMessage());
        }
    }


    /**
     * Mapping Moodle language to Mobile-ID language
     * Pay attention here, when Your Moodle has more languages!
     */
    public static function language_map($lang)
    {
        switch ($lang) {
            case 'en':
                return ENG::asType();
                break;
            case 'et':
                return EST::asType();
                break;
            case 'lt':
                return LIT::asType();
                break;
            case 'ru':
                return RUS::asType();
                break;
            default:
                return ENG::asType();
        }
    }

    /** Checks status and updates plugin's database entry for current login */
    public function update_status($sessionid)
    {
        global $CFG, $DB;

        $status = 'ERROR';

        try {
            $finalSessionStatus = $this->midRestClient
                ->getSessionStatusPoller()
                ->fetchFinalSessionStatus($sessionid);
            $authenticationHash = null;
            if ($mobileid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid))) {
                $hash = $mobileid->hash;
                $authenticationHash = MobileIdAuthenticationHashToSign::newBuilder()
                    ->withHashType(MobileIdAuthenticationHashToSign::DEFAULT_HASH_TYPE)
                    ->withHashInBase64($hash)
                    ->build();
                $authenticatedPerson = $this->midRestClient
                    ->createMobileIdAuthentication($finalSessionStatus, $authenticationHash)
                    ->getValidatedAuthenticationResult()
                    ->getAuthenticationIdentity();

                $user = $DB->get_record('user', array('id' => $mobileid->userid), 'idnumber');

                if (!$user->idnumber == $authenticatedPerson->getIdentityCode()) {
                    throw new \Exception('Identity codes mismatch');
                } else {
                    if ($dbid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid), 'id')) {
                        $record = new stdClass();
                        $record->id = $dbid->id;
                        $record->sessionid = $sessionid;
                        $record->status = $status = 'USER_AUTHENTICATED';
                        $DB->update_record('mobile_id_login', $record, false);
                    }
                }
            }
        } catch (\Exception $e) {
            if ($dbid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid), 'id')) {
                $record = new stdClass();
                $record->id = $dbid->id;
                $record->sessionid = $sessionid;
                $record->status = $status = 'ERROR';
                $DB->update_record('mobile_id_login', $record, false);
            }
        }
        return $status;
    }

    public function get_status($sessionid)
    {
        global $DB, $CFG;
        $mobileid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid), 'status');
        return is_object($mobileid) ? $mobileid->status : null;
    }

    public function get_control_code($sessionid)
    {
        global $DB;
        $mobileid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid), 'controlcode');
        return $mobileid->controlcode;
    }

    /** Deletes current login entry from database */
    private function clean_login($userid)
    {
        global $DB;
        $DB->delete_records('mobile_id_login', array('userid' => $userid));
    }

    /** Deletes used (unactive) sessions from database */
    private function clean_old_logins()
    {
        global $DB;
        $DB->delete_records_select('mobile_id_login', 'starttime < ?', array(time() - 120 /* 2 minutes */));
    }

    /** Authentication to Moodle here */
    public function login($sessionid)
    {
        global $DB, $CFG, $SESSION;

        $mobileid = $DB->get_record('mobile_id_login', array('sessionid' => $sessionid));
        if ($mobileid->status !== 'USER_AUTHENTICATED') {
            throw new \Exception('Invalid Mobile-ID login!');
        }
        if (!$usertologin = get_complete_user_data('id', $mobileid->userid)) {
            print_error('cannotfinduser', '', '', $mobileid->userid);
        }
        if ($usertologin->suspended) {
            throw new \Exception('User suspended!');
        }
        $usertologin->auth = $this->authtype;
        $USER = complete_user_login($usertologin);
        $SESSION->specialLogin = true;
        $this->clean_login($mobileid->userid);
        $this->clean_old_logins();
        if (optional_param('password_recovery', false, PARAM_BOOL))
            $SESSION->wantsurl = $CFG->wwwroot . '/login/change_password.php';
        $goto = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot;
        redirect($goto);
    }

    /** Login is going through file auth/mobile_id/login.php instead of usual login form */
    function user_login($username, $password)
    {
        return false;
    }

    /** Creates "login with Mobile-ID" link to Moodle login page */
    function loginpage_idp_list($wantsurl)
    {
        global $CFG;
        return array(
            array(
                'url' => new moodle_url($CFG->wwwroot . '/auth/mobile_id/login.php'),
                'iconurl' => $CFG->wwwroot . '/auth/mobile_id/pix/MoodleNupud-mid.png',
                // Need to copy this file (cp auth/mobile_id/images/mobiilid.gif pix/)
                'name' => get_string('login_button', 'auth_mobile_id')
            )
        );
    }

}
