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
 * Validates parsed blueprint rows against the quiz's question bank.
 *
 * Validation is all-or-nothing: every row is checked and the full list of
 * errors is returned, so the teacher can fix everything in one pass before
 * any quiz structure is created.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validator {

    /** @var category_helper */
    protected $cathelper;

    /** @var array name => int[] ids */
    protected $nametoids;

    /** @var array id => true */
    protected $validids;

    /** @var array categoryid => int cached available counts */
    protected $countcache = [];

    /**
     * @param category_helper $cathelper
     */
    public function __construct(category_helper $cathelper) {
        $this->cathelper = $cathelper;
        $this->nametoids = $cathelper->get_name_to_ids_map();
        $this->validids = $cathelper->get_valid_id_set();
    }

    /**
     * Validate the parsed rows.
     *
     * @param array $parsedrows output of {@see excel_parser::parse()}
     * @return array{rows: blueprint_row[], errors: string[]}
     */
    public function validate(array $parsedrows): array {
        $rows = [];
        $errors = [];

        if (empty($parsedrows)) {
            $errors[] = get_string('err_emptyblueprint', 'local_quizblueprint');
            return ['rows' => [], 'errors' => $errors];
        }

        foreach ($parsedrows as $parsed) {
            $rownum = $parsed['rownumber'];
            $cells = $parsed['cells'];
            $row = new blueprint_row($rownum);

            // Section name.
            $row->sectionname = $cells[excel_parser::KEY_SECTION] ?? '';
            if ($row->sectionname === '') {
                $errors[] = get_string('err_row_sectionname', 'local_quizblueprint', $rownum);
            }

            // Random flag.
            $randombool = $this->to_bool($cells[excel_parser::KEY_RANDOM] ?? '');
            if ($randombool === null) {
                $errors[] = get_string('err_row_random_invalid', 'local_quizblueprint', $rownum);
                $randombool = true; // Assume to continue collecting errors.
            }
            $row->random = $randombool;
            if ($randombool === false) {
                // v1 supports random-pool sections only.
                $errors[] = get_string('err_row_nonrandom_unsupported', 'local_quizblueprint', $rownum);
            }

            // Shuffle and page break (default to false when blank/invalid).
            $row->shuffle = (bool) ($this->to_bool($cells[excel_parser::KEY_SHUFFLE] ?? '') ?? false);
            $row->pagebreak = (bool) ($this->to_bool($cells[excel_parser::KEY_PAGEBREAK] ?? '') ?? false);

            // Question count: positive whole number.
            $countraw = $cells[excel_parser::KEY_COUNT] ?? '';
            $count = $this->to_positive_int($countraw);
            if ($count === null) {
                $errors[] = get_string('err_row_count_numeric', 'local_quizblueprint', $rownum);
            } else {
                $row->questioncount = $count;
            }

            // Mark per question: numeric > 0 (decimals allowed).
            $markraw = $cells[excel_parser::KEY_MARK] ?? '';
            $mark = $this->to_positive_float($markraw);
            if ($mark === null) {
                $errors[] = get_string('err_row_mark_numeric', 'local_quizblueprint', $rownum);
            } else {
                $row->markperquestion = $mark;
            }

            // Category resolution + availability.
            $row->categoryraw = $cells[excel_parser::KEY_CATEGORY] ?? '';
            $categoryid = $this->resolve_category($row->categoryraw, $rownum, $errors);
            if ($categoryid !== null) {
                $row->categoryid = $categoryid;
                if ($count !== null) {
                    $available = $this->available_count($categoryid);
                    if ($count > $available) {
                        $errors[] = get_string('err_row_count_exceeds', 'local_quizblueprint', (object) [
                            'row' => $rownum,
                            'requested' => $count,
                            'available' => $available,
                            'name' => $row->categoryraw,
                        ]);
                    }
                }
            }

            $rows[] = $row;
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Resolve a raw category cell (name or numeric id) to a category id.
     *
     * @param string $raw
     * @param int $rownum
     * @param array $errors collected by reference.
     * @return int|null resolved id or null on failure (error pushed).
     */
    protected function resolve_category(string $raw, int $rownum, array &$errors): ?int {
        $raw = trim($raw);
        if ($raw === '') {
            $errors[] = get_string('err_row_category_missing', 'local_quizblueprint', $rownum);
            return null;
        }

        // Numeric id path.
        if (ctype_digit($raw)) {
            $id = (int) $raw;
            if (isset($this->validids[$id])) {
                return $id;
            }
            $errors[] = get_string('err_row_category_notfound', 'local_quizblueprint',
                (object) ['row' => $rownum, 'name' => $raw]);
            return null;
        }

        // Name path.
        $key = \core_text::strtolower($raw);
        if (!isset($this->nametoids[$key])) {
            $errors[] = get_string('err_row_category_notfound', 'local_quizblueprint',
                (object) ['row' => $rownum, 'name' => $raw]);
            return null;
        }
        $ids = $this->nametoids[$key];
        if (count($ids) > 1) {
            $errors[] = get_string('err_row_category_ambiguous', 'local_quizblueprint', (object) [
                'row' => $rownum,
                'name' => $raw,
                'ids' => implode(', ', $ids),
            ]);
            return null;
        }
        return (int) reset($ids);
    }

    /**
     * Cached available-question count for a category.
     *
     * @param int $categoryid
     * @return int
     */
    protected function available_count(int $categoryid): int {
        if (!array_key_exists($categoryid, $this->countcache)) {
            $this->countcache[$categoryid] = $this->cathelper->count_questions($categoryid, false);
        }
        return $this->countcache[$categoryid];
    }

    /**
     * Normalise a yes/no style value to bool, or null if unrecognised.
     *
     * Accepts: Yes, No, Y, N, TRUE, FALSE, 1, 0 (case-insensitive).
     *
     * @param string $value
     * @return bool|null
     */
    protected function to_bool(string $value): ?bool {
        $v = \core_text::strtolower(trim($value));
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['yes', 'y', 'true', '1'], true)) {
            return true;
        }
        if (in_array($v, ['no', 'n', 'false', '0'], true)) {
            return false;
        }
        return null;
    }

    /**
     * Parse a positive whole number, or null.
     *
     * @param string $value
     * @return int|null
     */
    protected function to_positive_int(string $value): ?int {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d+$/', $value)) {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    /**
     * Parse a positive float (decimals allowed), or null.
     *
     * @param string $value
     * @return float|null
     */
    protected function to_positive_float(string $value): ?float {
        $value = trim($value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        $float = (float) $value;
        return $float > 0 ? $float : null;
    }
}
