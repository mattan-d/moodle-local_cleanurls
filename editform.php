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
require_once($CFG->libdir . '/formslib.php');

class seo_edit_form extends moodleform {
    protected $title = '';
    protected $description = '';

    public function __construct($actionurl, $courseid, $contextid, $sectionid) {
        $this->courseid = $courseid;
        $this->contextid = $contextid;
        $this->sectionid = $sectionid;

        parent::__construct($actionurl);
    }

    public function definition() {
        global $DB;

        $mform =& $this->_form;

        $course_mods = get_course_mods($this->courseid);
        $course = get_course($this->courseid);

        // Then show the fields about where this block appears.
        $mform->addElement('hidden', 'courseid', $this->courseid);
        $mform->addElement('hidden', 'contextid', $this->contextid);
        $mform->addElement('hidden', 'sectionid', $this->sectionid);

        $mform->addElement('header', 'editcampaignheader', 'Additional Meta Tags');
        $mform->addElement('text', 'title[0]', 'Title', 1);
        $mform->addElement('textarea', 'description[0]', get_string('description'), array('rows' => 15, 'cols' => 40));
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('header', 'editcampaignheader', 'Custom SEO URL\'S');

        if ($course_mods) {

            foreach ($course_mods as $module) {
                $cm = get_coursemodule_from_id(null, $module->id, $course->id, false, MUST_EXIST);
                $modurl = new moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);

                $mform->addElement('text', 'cm[' . $cm->id . ']', $cm->name);
                // $mform->addElement('html', $modurl);
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        foreach ($data['cm'] as $cm => $name) {
            if ($name) {
                $iexist = $DB->get_record('local_cleanurls', array('course' => $data['courseid'], 'name' => $name));
                if ($iexist && $cm != $iexist->cm) {
                    $errors['cm[' . $cm . ']'] = 'It cannot be saved. An item with that name already exists in the course.';
                }
            }
        }

        return $errors;
    }

    public function get_data() {
        $data = parent::get_data();
        return $data;
    }

    public function set_data($data) {

        $contextid = required_param('contextid', PARAM_INT);
        $sectionid = required_param('sectionid', PARAM_INT);

        $tmp = new stdClass();
        $tmp->cm = array();

        foreach ($data as $cm) {
            $tmp->cm[$cm->cm] = $cm->name;

            if ($cm->contextid == $contextid && $cm->sectionid == $sectionid) {
                $tmp->title[$cm->cm] = $cm->title;
                $tmp->description[$cm->cm] = $cm->description;
            }
        }

        parent::set_data($tmp);
    }
}

$courseid = required_param('courseid', PARAM_INT);
$contextid = required_param('contextid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$course = get_course($courseid);

$context = context_system::instance();
$PAGE->set_context($context);

$urlparams = ['courseid' => $courseid, 'contextid' => $contextid];
$customseo = new moodle_url('/local/cleanurls/editform.php', $urlparams);
$courseURL = new moodle_url('/course/view.php', array('id' => $courseid));

$PAGE->set_url('/local/cleanurls/editform.php', $urlparams);
$PAGE->set_pagelayout('admin');

$mform = new seo_edit_form($PAGE->url, $courseid, $contextid, $sectionid);

$cleanurls = $DB->get_records('local_cleanurls', array('course' => $courseid));
$mform->set_data($cleanurls);

if ($mform->is_cancelled()) {
    redirect($customseo);

} else if ($data = $mform->get_data()) {
    $cmid = $DB->get_records('local_cleanurls', array('course' => $courseid));

    if ($cmid) {
        $DB->delete_records('local_cleanurls', array('course' => $courseid, 'contextid' => 0));
        $DB->delete_records('local_cleanurls', array('course' => $courseid, 'contextid' => $contextid, 'sectionid' => $sectionid));
    }

    $metadata = new stdClass();
    $metadata->title = $data->title[0];
    $metadata->description = $data->description[0];
    $metadata->contextid = $data->contextid;
    $metadata->sectionid = $data->sectionid;
    $metadata->course = $courseid;
    $DB->insert_record('local_cleanurls', $metadata);

    foreach ($data->cm as $modid => $modname) {
        $module = new stdClass();
        $module->cm = $modid;
        $module->name = $modname;
        $module->course = $courseid;

        if ($modname) {
            $DB->insert_record('local_cleanurls', $module);
        }
    }

    $cache = cache::make('local_cleanurls', 'outgoing');
    $cache->purge();

    redirect($courseURL, 'Custom SEO URL\'s Updated!');

} else {

    $PAGE->set_title($course->fullname);
    $PAGE->set_heading($course->fullname);

    $PAGE->navbar->add(get_string('local'));
    $PAGE->navbar->add(get_string('pluginname', 'local_cleanurls'));
    $PAGE->navbar->add($course->fullname, $courseURL);
    $PAGE->navbar->add('Custom SEO URL\'s', $customseo);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($course->fullname, 2);

    $mform->display();

    echo $OUTPUT->footer();
}
