<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Database upgrades for the Local Cleanurls
 *
 * @package   local_cleanurls
 * @copyright 2023 CentricApp <support@centricapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_cleanurls database.
 *
 * @param int $oldversion The version number of the plugin that was installed.
 * @return boolean
 */
function xmldb_local_cleanurls_upgrade($oldversion)
{
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023061301) {

        $table = new xmldb_table('local_cleanurls');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, 0);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('cm', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // savepoint reached.
        upgrade_plugin_savepoint(true, 2023061301, 'local', 'cleanurls');

    }

    return true;
}