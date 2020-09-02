# Moodle Mobile-ID authentication plugin

Moodle auth plugin, that enables Mobile-ID logins.
Works also without Javascript.
License: GPL

## Installation instructions

1. Copy files into <MoodleRoot>/auth/mobile_id/ directory (or just "git clone git://github.com/mangus/mobile_id")
1. Install the plugin. See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins.
1. To configure the plugin, add these lines to the config.php
```
$CFG->relyingPartyUUID = '00000000-0000-0000-0000-000000000000';
$CFG->relyingPartyName = 'DEMO';
$CFG->hostUrl = 'https://tsp.demo.sk.ee/mid-api';
```
