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

namespace local_quizblueprint\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event: a quiz blueprint was imported and quiz structure created.
 *
 * Logged via the standard Moodle Events API so the action is captured by the
 * configured logstore (User, Quiz, date/time, rows processed, errors).
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 *      @var int $sections number of sections created.
 *      @var int $slots number of random slots created.
 *      @var int $rows number of blueprint rows processed.
 * }
 */
class blueprint_imported extends \core\event\base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'quiz';
    }

    /**
     * @return string the localised event name.
     */
    public static function get_name() {
        return get_string('event_blueprint_imported', 'local_quizblueprint');
    }

    /**
     * @return string a human-readable description of what happened.
     */
    // public function get_description() {
    //     $sections = $this->other['sections'] ?? 0;
    //     $slots = $this->other['slots'] ?? 0;
    //     return "The user with id '{$this->userid}' imported a quiz blueprint into the quiz " .
    //            "with course module id '{$this->contextinstanceid}', creating {$sections} " .
    //            "section(s) and {$slots} random question slot(s).";
    // }
    public function get_description() {
        $a = new \stdClass();
        $a->userid = $this->userid;
        $a->cmid = $this->contextinstanceid;
        $a->sections = $this->other['sections'] ?? 0;
        $a->slots = $this->other['slots'] ?? 0;

        return get_string('event_blueprint_imported_desc', 'local_quizblueprint', $a);
    }

    /**
     * @return \moodle_url the relevant quiz editing URL.
     */
    public function get_url() {
        return new \moodle_url('/mod/quiz/edit.php', ['cmid' => $this->contextinstanceid]);
    }

    /**
     * Validate custom event data.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['sections']) || !isset($this->other['slots'])) {
            throw new \coding_exception('The \'sections\' and \'slots\' values must be set in $other.');
        }
    }
}
