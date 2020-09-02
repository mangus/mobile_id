<?php

function xmldb_auth_mobile_id_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020082600) {

        $table = new xmldb_table('mobile_id_login');

        $index = new xmldb_index('mobiidlogi_ses_uix', XMLDB_INDEX_UNIQUE, ['sesscode']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('sesscode');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('sessionid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null, 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('sessionid_index', XMLDB_INDEX_UNIQUE, ['sessionid']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $field = new xmldb_field('hash', XMLDB_TYPE_TEXT, '', null, XMLDB_NOTNULL, null, null, 'controlcode');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020082600, 'auth', 'mobile_id');
    }

    return true;
}
