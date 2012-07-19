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

    /** Constructor */
    function auth_plugin_mobile_id() {
        $this->authtype = 'mobile_id';
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

