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
 * Resolves the question categories available to a quiz and counts the
 * questions inside them, using the Moodle 4.x question bank schema.
 *
 * In Moodle 4.x a "question" is no longer stored directly against a category.
 * The chain is:
 *   question_categories (id)
 *     <- question_bank_entries.questioncategoryid
 *        <- question_versions.questionbankentryid (status = 'ready', latest version)
 *           -> question.id (we exclude qtype = 'random' helper rows)
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_helper {

    /** @var \context_module The quiz module context. */
    protected $context;

    /** @var int[] Ordered list of context ids the quiz can use questions from. */
    protected $contextids;

    /**
     * @param \context_module $context the quiz module context.
     */
    public function __construct(\context_module $context) {
        $this->context = $context;
        // get_parent_context_ids(true) returns this context plus every parent
        // up to the system context (module -> course -> coursecat... -> system).
        // These are exactly the contexts whose question categories a teacher
        // editing this quiz may select random questions from.
        $this->contextids = $context->get_parent_context_ids(true);
    }

    /**
     * Return all question categories visible to this quiz.
     *
     * @return array list of objects {id, name, contextid, parent, info} ordered by context then name.
     */
    public function get_categories(): array {
        global $DB;

        if (empty($this->contextids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($this->contextids, SQL_PARAMS_NAMED, 'ctx');
        $sql = "SELECT qc.id, qc.name, qc.contextid, qc.parent, qc.info
                  FROM {question_categories} qc
                 WHERE qc.contextid $insql
              ORDER BY qc.contextid, qc.name";
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Count the number of usable questions in a single category.
     *
     * Counts the latest "ready" version of each question bank entry in the
     * category, excluding the internal "random" helper qtype.
     *
     * @param int $categoryid
     * @param bool $includesubcategories whether to also count descendant categories.
     * @return int number of available questions.
     */
    public function count_questions(int $categoryid, bool $includesubcategories = false): int {
        global $DB;

        $catids = [$categoryid];
        if ($includesubcategories) {
            $catids = array_merge($catids, $this->get_subcategory_ids($categoryid));
        }

        list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');

        // 'ready' is the stable string value of question_version_status::QUESTION_STATUS_READY.
        $params['ready'] = 'ready';

        $sql = "SELECT COUNT(qbe.id)
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                       ON qv.questionbankentryid = qbe.id
                      AND qv.status = :ready
                      AND qv.version = (
                              SELECT MAX(qv2.version)
                                FROM {question_versions} qv2
                               WHERE qv2.questionbankentryid = qbe.id
                                 AND qv2.status = :ready2
                          )
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid $insql
                   AND q.qtype <> :random";
        $params['ready2'] = 'ready';
        $params['random'] = 'random';

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * List question names within a category (latest ready version).
     *
     * @param int $categoryid
     * @param int $limit maximum rows to return (0 = no limit).
     * @return array list of objects {name}.
     */
    public function list_questions(int $categoryid, int $limit = 0): array {
        global $DB;

        $params = ['cat' => $categoryid, 'ready' => 'ready', 'ready2' => 'ready', 'random' => 'random'];
        $sql = "SELECT q.id, q.name
                  FROM {question_bank_entries} qbe
                  JOIN {question_versions} qv
                       ON qv.questionbankentryid = qbe.id
                      AND qv.status = :ready
                      AND qv.version = (
                              SELECT MAX(qv2.version)
                                FROM {question_versions} qv2
                               WHERE qv2.questionbankentryid = qbe.id
                                 AND qv2.status = :ready2
                          )
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE qbe.questioncategoryid = :cat
                   AND q.qtype <> :random
              ORDER BY q.name";
        return array_values($DB->get_records_sql($sql, $params, 0, $limit));
    }

    /**
     * Build a map of category name => list of matching category ids (visible to this quiz).
     *
     * Category names are not globally unique, so each name maps to an array of ids.
     * The blueprint validator uses this to detect ambiguous names.
     *
     * @return array name (lowercased, trimmed) => int[] ids
     */
    public function get_name_to_ids_map(): array {
        $map = [];
        foreach ($this->get_categories() as $cat) {
            $key = \core_text::strtolower(trim($cat->name));
            $map[$key][] = (int) $cat->id;
        }
        return $map;
    }

    /**
     * Get the set of valid category ids visible to this quiz (for numeric-id lookups).
     *
     * @return array id => true
     */
    public function get_valid_id_set(): array {
        $set = [];
        foreach ($this->get_categories() as $cat) {
            $set[(int) $cat->id] = true;
        }
        return $set;
    }

    /**
     * Recursively collect descendant category ids of a category.
     *
     * @param int $categoryid
     * @return int[]
     */
    protected function get_subcategory_ids(int $categoryid): array {
        global $DB;
        $result = [];
        $children = $DB->get_records('question_categories', ['parent' => $categoryid], '', 'id');
        foreach ($children as $child) {
            $result[] = (int) $child->id;
            $result = array_merge($result, $this->get_subcategory_ids((int) $child->id));
        }
        return $result;
    }
}
