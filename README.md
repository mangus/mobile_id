mobile_id
=========

Moodle auth plugin, that enables Mobile-ID logins.
Works also without Javascript.
License: GPL

Installation instructions
=========================

1. Copy files into <MoodleRoot>/auth/mobile_id/ directory (or just "git clone git://github.com/mangus/mobile_id")
2. Change the $sitename and $sitemessage variables in auth.php lines 14-15
3. Check lines 148-154 of auth.php -- probably You can comment these lines out
4. Edit Your Moodles login pages to add link/image to /auth/mobile_id/login.php

If You find bugs, report them in https://github.com/mangus/mobile_id/issues -- thanks!

