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
 * Creates quiz structure (sections + random question slots + marks) from a
 * validated blueprint, inside a single database transaction.
 *
 * Layout model (v1): each blueprint row becomes one quiz section occupying its
 * own page. Because Moodle requires every section to begin at the first slot of
 * a page, sections inherently start on new pages -- this is what produces the
 * page break between sections. All random slots for a row are placed on that
 * one page.
 *
 * Moodle APIs used (verified against MOODLE_403_STABLE):
 *   - \mod_quiz\quiz_settings::create_for_cmid()
 *   - \mod_quiz\structure::create_for_quiz()
 *   - structure::add_random_questions($addonpage, $number, $filtercondition)
 *   - structure::add_section_heading($pagenumber, $heading)
 *   - structure::set_section_heading() / set_section_shuffle()
 *   - structure::get_slots_in_section() / get_slot_by_number() / update_slot_maxmark()
 *   - grade_calculator::recompute_quiz_sumgrades()
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blueprint_builder {

    /** @var int Quiz course module id. */
    protected $cmid;

    /**
     * @param int $cmid quiz course module id.
     */
    public function __construct(int $cmid) {
        $this->cmid = $cmid;
    }

    /**
     * Inspect the current quiz state for pre-import checks (read-only).
     *
     * Used by the controller to block import on attempted quizzes and to warn
     * when the quiz already contains questions/sections.
     *
     * @param int $cmid quiz course module id.
     * @return \stdClass {canedit:bool, questioncount:int, sectioncount:int}
     */
    public static function inspect(int $cmid): \stdClass {
        $settings = \mod_quiz\quiz_settings::create_for_cmid($cmid);
        $structure = \mod_quiz\structure::create_for_quiz($settings);

        $info = new \stdClass();
        $info->canedit = $structure->can_be_edited();
        $info->questioncount = $structure->get_question_count();
        $info->sectioncount = $structure->get_section_count();
        return $info;
    }

    /**
     * Build the quiz structure from validated rows.
     *
     * @param blueprint_row[] $rows validated rows (in spreadsheet order).
     * @return \stdClass {sections:int, slots:int}
     * @throws \Throwable rethrown after rollback if anything fails (no partial import).
     */
    public function build(array $rows): \stdClass {
        global $DB;

        $settings = \mod_quiz\quiz_settings::create_for_cmid($this->cmid);

        // Refuse to touch a quiz that already has attempts -- changing its
        // structure after students have attempted it would corrupt grading.
        $guard = \mod_quiz\structure::create_for_quiz($settings);
        if (!$guard->can_be_edited()) {
            throw new \moodle_exception('err_quizhasattempts', 'local_quizblueprint');
        }

        $quiz = $settings->get_quiz();

        $sectionscreated = 0;
        $slotscreated = 0;

        // One transaction wraps the whole import. If any row fails the entire
        // import is rolled back, so the quiz is never left half-built.
        $transaction = $DB->start_delegated_transaction();
        try {
            $page = 1;
            $isfirstrow = true;

            foreach ($rows as $row) {
                // (Re)build the live structure so it reflects previous rows.
                $structure = \mod_quiz\structure::create_for_quiz($settings);

                // --- 1. Add the random question slots for this section. ---
                // Each of the $questioncount slots draws one question from the
                // category. They are all placed on the current page.
                $filtercondition = [
                    'filter' => [
                        'category' => [
                            'jointype' => \core_question\local\bank\condition::JOINTYPE_DEFAULT,
                            'values' => [$row->categoryid],
                            'filteroptions' => ['includesubcategories' => false],
                        ],
                    ],
                ];
                $structure->add_random_questions($page, $row->questioncount, $filtercondition);
                $slotscreated += $row->questioncount;

                // --- 2. Create / update the section heading for this page. ---
                $structure = \mod_quiz\structure::create_for_quiz($settings);
                $sectionid = $this->ensure_section($structure, $quiz->id, $page, $row, $isfirstrow);
                $isfirstrow = false;
                $sectionscreated++;

                // --- 3. Set the mark for every slot in this section. ---
                $structure = \mod_quiz\structure::create_for_quiz($settings);
                foreach ($structure->get_slots_in_section($sectionid) as $slotnumber) {
                    $slot = $structure->get_slot_by_number($slotnumber);
                    $structure->update_slot_maxmark($slot, $row->markperquestion);
                }

                // Next section goes on the next page (forces the page break).
                $page++;
            }

            // --- 4. Recalculate the quiz total grade (sumgrades). ---
            $settings->get_grade_calculator()->recompute_quiz_sumgrades();

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            // rollback() rethrows the exception; the caller reports it.
            $transaction->rollback($e);
        }

        $result = new \stdClass();
        $result->sections = $sectionscreated;
        $result->slots = $slotscreated;
        return $result;
    }

    /**
     * Ensure the section heading exists for the given page and apply its settings.
     *
     * Moodle always keeps a default section starting at slot 1. For the first
     * blueprint row we update that default section; for later rows we add a new
     * section heading at the start of the row's page.
     *
     * @param \mod_quiz\structure $structure
     * @param int $quizid
     * @param int $page
     * @param blueprint_row $row
     * @param bool $isfirstrow
     * @return int the section id.
     */
    protected function ensure_section(\mod_quiz\structure $structure, int $quizid, int $page,
            blueprint_row $row, bool $isfirstrow): int {
        global $DB;

        if ($isfirstrow) {
            $existing = $DB->get_record('quiz_sections',
                ['quizid' => $quizid, 'firstslot' => 1], '*', IGNORE_MISSING);
            if ($existing) {
                $structure->set_section_heading($existing->id, $row->sectionname);
                // Reload before changing shuffle to keep cached data consistent.
                $structure = \mod_quiz\structure::create_for_quiz(
                    \mod_quiz\quiz_settings::create($quizid));
                $structure->set_section_shuffle($existing->id, $row->shuffle ? 1 : 0);
                return (int) $existing->id;
            }
        }

        // Add a brand new section heading at the first slot of this page.
        $sectionid = $structure->add_section_heading($page, $row->sectionname);
        $structure = \mod_quiz\structure::create_for_quiz(
            \mod_quiz\quiz_settings::create($quizid));
        $structure->set_section_shuffle($sectionid, $row->shuffle ? 1 : 0);
        return (int) $sectionid;
    }
}
