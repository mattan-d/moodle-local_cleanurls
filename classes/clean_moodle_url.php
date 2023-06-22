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
 * The main cleaning and uncleaning logic
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cleanurls;

use local_cleanurls\local\cleaner\courseformat_cleaner_interface;
use local_cleanurls\local\uncleaner\uncleaner;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The main cleaning and uncleaning logic
 *
 * @package    local_cleanurls
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_moodle_url extends moodle_url {
    public static function extract_moodle_path(&$path, &$moodlepath) {
        global $CFG;

        // If moodle is installed inside a dir like example.com/somepath/moodle/index.php
        // then remove the 'somepath/moodle' part and store for later.
        $slashstart = strlen(parse_url($CFG->wwwroot, PHP_URL_SCHEME)) + 3;
        $slashpos = strpos($CFG->wwwroot, '/', $slashstart);

        $moodlepath = '';
        if ($slashpos) {
            $moodlepath = substr($CFG->wwwroot, $slashpos);
            $path = substr($path, strlen($moodlepath));
            self::log("Removed wwwroot ({$moodlepath}) from path: {$path}");
        }
    }

    /**
     * A log util for debugging
     *
     * @param string $msg an log message
     */
    public static function log($msg) {
        if (get_config('local_cleanurls', 'debugging')) {
            // @codingStandardsIgnoreStart
            error_log($msg);
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * A util for crafting human readable url components
     *
     * This is non reversible and only used to augment a url to make it more
     * obvious, but isn't used at all for routing. Usually it is prefixed
     * with and id such as /page/1234-some-nice-name
     *
     * @param string $string a string to url escape and prettify
     * @param boolean $dashprefix if present a dash is prepended
     * @return string
     */
    public static function sluggify($string, $dashprefix) {
        $string = wordpress_util::sanitize_title_with_dashes($string);
        return ($dashprefix ? '-' : '') . $string;
    }

    /**
     * Provides a setter not available in the parent class.
     *
     * @param string $path
     */
    public function set_path($path) {
        $this->path = $path;
    }

    /**
     * Locates the class that should handle the course format cleaner and uncleaner.
     *
     * @param string $format
     * @return string
     */
    public static function get_format_support($format) {
        $classname = "\\format_{$format}\\cleanurls_support";
        if (!class_exists($classname)) {
            $classname = "\\local_cleanurls\\local\\courseformat\\{$format}";
            if (!class_exists($classname)) {
                return null;
            }
        }

        if (!is_a($classname, uncleaner::class, true)) {
            debugging("Class '{$classname}' must inherit uncleaner.");
            return null;
        }

        if (!is_a($classname, courseformat_cleaner_interface::class, true)) {
            debugging("Class '{$classname}' must implement courseformat_cleaner_interface.");
            return null;
        }

        return $classname;
    }
}
