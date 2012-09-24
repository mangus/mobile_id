<?php

require_once('../../config.php');
require_once('auth.php');

$logincheck = new auth_plugin_mobile_id();
return $logincheck->check_status(required_param('sesscode'));

