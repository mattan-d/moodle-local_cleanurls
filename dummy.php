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
 *
 * @package   local_cleanurls
 * @copyright 2023 CentricApp <support@centricapp.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB;

$cms = $DB->get_records('course_modules');
foreach ($cms as $cm) {
    $cm = get_coursemodule_from_id(null, $cm->id, $cm->course, false, MUST_EXIST);

    $customname = $DB->get_record('local_cleanurls', array('cm' => $cm->id, 'course' => $cm->course));
    if (!$customname) {
        $module = new stdClass();
        $module->cm = $cm->id;
        $module->name = $cm->modname . '-' . $cm->id;
        $module->course = $cm->course;
        $DB->insert_record('local_cleanurls', $module);

        echo $cm->modname . PHP_EOL;
    }

}