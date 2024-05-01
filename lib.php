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

function local_cleanurls_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE, $DB, $USER;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    if (!$PAGE->course->id) {
        return;
    }

    $context = context_course::instance($PAGE->course->id);
    if (!has_capability('moodle/course:update', $context)) {
        return;
    }

    $sectionid = optional_param('section', 0, PARAM_INT);
    $editformpage = new moodle_url('/local/cleanurls/editform.php', [
            'courseid' => $PAGE->course->id,
            'contextid' => $PAGE->context->id,
            'sectionid' => $sectionid
    ]);
    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $str = get_string('pluginname', 'local_cleanurls');
        $node = navigation_node::create(
                $str,
                $editformpage,
                navigation_node::NODETYPE_LEAF,
                null,
                'editseo',
                new pix_icon('t/contextmenu', $str)
        );
        if ($PAGE->url->compare($editformpage, URL_MATCH_BASE)) {
            $node->make_active();
        }
        $settingnode->add_node($node);
    }

    if ($PAGE->user_is_editing() && is_siteadmin($USER)) {
        echo html_writer::tag('a', 'SEO', ['class' => 'cleanurl-btn', 'href' => $editformpage]);
    }
}