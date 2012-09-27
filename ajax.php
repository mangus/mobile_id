<?php

require_once('../../config.php');
require_once('auth.php');

$logincheck = new auth_plugin_mobile_id();
$canlogin = $logincheck->can_login(required_param('sesscode', PARAM_ALPHANUM));
if (!optional_param('noajax', false, PARAM_BOOL)) {
    die($canlogin);
} else {
    if ($canlogin) {
        die('go-lgoin'); // Redirect to automatic login
    } else {
        die('go-back'); // Redirect to mobile-id login page
    }
} 

