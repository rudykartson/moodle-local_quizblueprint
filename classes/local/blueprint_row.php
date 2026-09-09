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

namespace local_quizblueprint\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable value object describing one row of a parsed blueprint.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blueprint_row {

    /** @var int 1-based spreadsheet row number (for error messages). */
    public $rownumber;

    /** @var string Section name / heading. */
    public $sectionname = '';

    /** @var string Raw category value as typed by the teacher (name or numeric id). */
    public $categoryraw = '';

    /** @var int|null Resolved category id (set by the validator). */
    public $categoryid = null;

    /** @var bool Whether this section uses random questions. */
    public $random = true;

    /** @var int Number of (random) questions requested. */
    public $questioncount = 0;

    /** @var float Mark per question. */
    public $markperquestion = 1.0;

    /** @var bool Shuffle questions within the section. */
    public $shuffle = false;

    /** @var bool Page break flag (see builder for behaviour). */
    public $pagebreak = false;

    /**
     * @param int $rownumber 1-based spreadsheet row number.
     */
    public function __construct(int $rownumber) {
        $this->rownumber = $rownumber;
    }
}
