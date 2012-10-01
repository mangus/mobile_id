<?php

require_once('../../config.php');
require_once('auth.php');

$logincheck = new auth_plugin_mobile_id();
$sesscode = required_param('sesscode', PARAM_ALPHANUM);

$logincheck->update_status($sesscode);
$canlogin = $logincheck->can_login($sesscode);

if (!optional_param('noajax', false, PARAM_BOOL)) {
    echo $canlogin;
} else {
    if ($canlogin) {
        redirect('/auth/mobile_id/login.php?waitmore=' . $sesscode);
    } else {
        redirect('/auth/mobile_id/login.php?startlogin=' . $sesscode);
    }
} 

