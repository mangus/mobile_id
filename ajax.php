<?php

require_once('../../config.php');
require_once('auth.php');

$logincheck = new auth_plugin_mobile_id();
$sesscode = (int) required_param('sesscode', PARAM_ALPHANUM);

$logincheck->update_status($sesscode);
$status = $logincheck->get_status($sesscode);

if (!optional_param('noajax', false, PARAM_BOOL)) {
    echo $status;
} else {
    switch ($status) {
        case 'USER_AUTHENTICATED':
            redirect('/auth/mobile_id/login.php?startlogin=' . $sesscode);
            break;
        case 'EXPIRED_TRANSACTION':
            redirect('/auth/mobile_id/login.php?timeout=' . $sesscode);
            break;
        case 'OUTSTANDING_TRANSACTION':
            redirect('/auth/mobile_id/login.php?waitmore=' . $sesscode);
            break; 
        default:
            redirect('/auth/mobile_id/login.php?error=1&status=' . $status);
    }
} 

