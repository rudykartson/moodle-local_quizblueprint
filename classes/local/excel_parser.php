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

use PhpOffice\PhpSpreadsheet\IOFactory;

defined('MOODLE_INTERNAL') || die();

/**
 * Parses an uploaded blueprint workbook into raw, header-mapped rows.
 *
 * The parser is deliberately tolerant: it maps columns by header name (so
 * column order does not matter) and returns raw cell strings. All semantic
 * validation is performed separately by {@see validator}.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class excel_parser {

    /** Canonical internal keys for the blueprint columns. */
    const KEY_SECTION   = 'section';
    const KEY_CATEGORY  = 'category';
    const KEY_RANDOM    = 'random';
    const KEY_COUNT     = 'count';
    const KEY_MARK      = 'mark';
    const KEY_SHUFFLE   = 'shuffle';
    const KEY_PAGEBREAK = 'pagebreak';

    /**
     * Map of lowercased header text => canonical key.
     *
     * @return array
     */
    protected function header_aliases(): array {
        return [
            \core_text::strtolower(get_string('col_sectionname', 'local_quizblueprint'))     => self::KEY_SECTION,
            \core_text::strtolower(get_string('col_category', 'local_quizblueprint'))        => self::KEY_CATEGORY,
            \core_text::strtolower(get_string('col_random', 'local_quizblueprint'))          => self::KEY_RANDOM,
            \core_text::strtolower(get_string('col_questioncount', 'local_quizblueprint'))   => self::KEY_COUNT,
            \core_text::strtolower(get_string('col_markperquestion', 'local_quizblueprint')) => self::KEY_MARK,
            \core_text::strtolower(get_string('col_shuffle', 'local_quizblueprint'))         => self::KEY_SHUFFLE,
            \core_text::strtolower(get_string('col_pagebreak', 'local_quizblueprint'))       => self::KEY_PAGEBREAK,
        ];
    }

    /**
     * Parse the workbook at the given path.
     *
     * @param string $path absolute path to the uploaded .xlsx/.xls file.
     * @return array list of ['rownumber' => int, 'cells' => array<key,string>]
     * @throws \moodle_exception on unreadable file or missing Blueprint sheet.
     */
    public function parse(string $path): array {
        global $CFG;
        // PhpSpreadsheet ships with Moodle but is not globally autoloaded.
        // require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            throw new \moodle_exception('err_cannotread', 'local_quizblueprint', '', $e->getMessage());
        }

        // Prefer a sheet literally named "Blueprint"; otherwise use the first sheet.
        $sheetname = get_string('sheet_blueprint', 'local_quizblueprint');
        $sheet = $spreadsheet->getSheetByName($sheetname);
        if ($sheet === null) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $rows = $sheet->toArray(null, true, false, false);
        if (empty($rows)) {
            throw new \moodle_exception('err_emptyblueprint', 'local_quizblueprint');
        }

        // First non-empty row is the header.
        $headercols = $this->map_headers(array_shift($rows));
        if (empty($headercols)) {
            throw new \moodle_exception('err_nosheet', 'local_quizblueprint', '', $sheetname);
        }

        $result = [];
        $rownumber = 1; // Header was row 1.
        foreach ($rows as $raw) {
            $rownumber++;
            if ($this->is_blank_row($raw)) {
                continue;
            }
            $cells = [];
            foreach ($headercols as $colindex => $key) {
                $cells[$key] = isset($raw[$colindex]) ? trim((string) $raw[$colindex]) : '';
            }
            $result[] = ['rownumber' => $rownumber, 'cells' => $cells];
        }

        return $result;
    }

    /**
     * Map header cells to canonical keyed column indexes.
     *
     * @param array $headerrow
     * @return array column index (int) => canonical key (string)
     */
    protected function map_headers(array $headerrow): array {
        $aliases = $this->header_aliases();
        $map = [];
        foreach ($headerrow as $index => $value) {
            $key = \core_text::strtolower(trim((string) $value));
            if (isset($aliases[$key])) {
                $map[$index] = $aliases[$key];
            }
        }
        return $map;
    }

    /**
     * Is every cell in this row blank?
     *
     * @param array $raw
     * @return bool
     */
    protected function is_blank_row(array $raw): bool {
        foreach ($raw as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}
