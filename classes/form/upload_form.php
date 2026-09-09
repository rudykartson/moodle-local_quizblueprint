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

namespace local_quizblueprint\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Upload form for the completed blueprint spreadsheet.
 *
 * Uses the Moodle Forms API. The filemanager restricts uploads to spreadsheet
 * types, and the form embeds the cmid plus sesskey (the framework adds and
 * checks sesskey automatically on submission).
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'action', 'preview');
        $mform->setType('action', PARAM_ALPHA);

        $options = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.xlsx', '.xls'],
        ];
        $mform->addElement('filepicker', 'blueprintfile',
            get_string('uploadblueprint', 'local_quizblueprint'), null, $options);
        $mform->addRule('blueprintfile', get_string('err_noupload', 'local_quizblueprint'),
            'required', null, 'client');

        $this->add_action_buttons(false, get_string('uploadblueprint', 'local_quizblueprint'));
    }
}
