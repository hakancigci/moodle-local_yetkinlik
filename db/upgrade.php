<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the local_yetkinlik plugin.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_local_yetkinlik_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026082200) {

        // 1. We define the local_competence_scale_map table.
        $table1 = new xmldb_table('local_yetkinlik_scale_map');

        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('scaleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('scaleitemindex', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('minpercent', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '0.00');
        $table1->add_field('maxpercent', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '100.00');

        $table1->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table1)) {
            $dbman->create_table($table1);
        }

        // We are notifying Moodle that this stage has been successfully completed.
        upgrade_plugin_savepoint(true, 2026082200, 'local', 'yetkinlik');
    }

    return true;
}
