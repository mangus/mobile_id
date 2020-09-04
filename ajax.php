<?php

require_once('../../config.php');
require_once('auth.php');

$logincheck = new auth_plugin_mobile_id();
$sessionid = required_param('sessionid', PARAM_TEXT);
$status = $logincheck->update_status($sessionid);

if (!optional_param('noajax', false, PARAM_BOOL)) {
    echo $status;
} else {
    switch ($status) {
        case 'USER_AUTHENTICATED':
            redirect('/auth/mobile_id/login.php?startlogin=' . $sessionid);
            break;

        default:
            redirect('/auth/mobile_id/login.php?error=1&status=' . $status);
    }
} 
