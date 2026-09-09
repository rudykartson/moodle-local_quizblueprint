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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates the downloadable Excel blueprint template.
 *
 * Uses PhpSpreadsheet, which Moodle bundles and autoloads under the
 * \PhpOffice\PhpSpreadsheet namespace, so no extra dependency is required.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_generator {

    /** @var category_helper */
    protected $cathelper;

    /**
     * @param \context_module $context the quiz module context.
     */
    public function __construct(\context_module $context) {
        $this->cathelper = new category_helper($context);
    }

    /**
     * Build the spreadsheet object with all three sheets.
     *
     * @return Spreadsheet
     */
    public function build(): Spreadsheet {
        global $CFG;
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Moodle')
            ->setTitle(get_string('pluginname', 'local_quizblueprint'));
        $categories = $this->cathelper->get_categories();
        $this->build_blueprint_sheet($spreadsheet);
        $this->build_category_sheet($spreadsheet, $categories);
        $this->build_question_sheet($spreadsheet, $categories);
        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    /**
     * Sheet 1: the blueprint the teacher fills in.
     *
     * @param Spreadsheet $spreadsheet
     */
    protected function build_blueprint_sheet(Spreadsheet $spreadsheet): void {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(get_string('sheet_blueprint', 'local_quizblueprint'));

        $headers = [
            get_string('col_sectionname', 'local_quizblueprint'),
            get_string('col_category', 'local_quizblueprint'),
            get_string('col_random', 'local_quizblueprint'),
            get_string('col_questioncount', 'local_quizblueprint'),
            get_string('col_markperquestion', 'local_quizblueprint'),
            get_string('col_shuffle', 'local_quizblueprint'),
            get_string('col_pagebreak', 'local_quizblueprint'),
        ];
        $this->write_header_row($sheet, $headers);

        // Two illustrative example rows.
        $examples = [
            ['Mining Trucks Basics', 'Mining Trucks', 'Yes', 10, 2, 'Yes', 'Yes'],
            ['Mining Safety', 'Safety', 'Yes', 5, 1, 'No', 'Yes'],
        ];
        $row = 2;
        foreach ($examples as $ex) {
            $col = 'A';
            foreach ($ex as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Sheet 2: category reference auto-generated from the question bank.
     *
     * @param Spreadsheet $spreadsheet
     * @param array $categories
     */
    protected function build_category_sheet(Spreadsheet $spreadsheet, array $categories): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(get_string('sheet_categories', 'local_quizblueprint'));

        $headers = [
            get_string('col_category', 'local_quizblueprint'),
            get_string('col_availablequestions', 'local_quizblueprint'),
            get_string('col_categoryid', 'local_quizblueprint'),
        ];
        $this->write_header_row($sheet, $headers);

        $row = 2;
        foreach ($categories as $cat) {
            $count = $this->cathelper->count_questions((int) $cat->id, false);
            $sheet->setCellValue('A' . $row, $cat->name);
            $sheet->setCellValue('B' . $row, $count);
            // Category ID lets teachers disambiguate duplicate names if needed.
            $sheet->setCellValueExplicit(
                'C' . $row,
                (string) $cat->id,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $row++;
        }

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Sheet 3 (optional): every question name within each category.
     *
     * @param Spreadsheet $spreadsheet
     * @param array $categories
     */
    protected function build_question_sheet(Spreadsheet $spreadsheet, array $categories): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(get_string('sheet_questions', 'local_quizblueprint'));

        $headers = [
            get_string('col_category', 'local_quizblueprint'),
            get_string('col_questionname', 'local_quizblueprint'),
        ];
        $this->write_header_row($sheet, $headers);

        $row = 2;
        foreach ($categories as $cat) {
            // Cap per category to keep the workbook a sensible size on huge banks.
            foreach ($this->cathelper->list_questions((int) $cat->id, 1000) as $q) {
                $sheet->setCellValue('A' . $row, $cat->name);
                $sheet->setCellValue('B' . $row, $q->name);
                $row++;
            }
        }

        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Write and style a bold, shaded header row.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array $headers
     */
    protected function write_header_row($sheet, array $headers): void {
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }
        $lastcol = chr(ord('A') + count($headers) - 1);
        $style = $sheet->getStyle('A1:' . $lastcol . '1');
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9E1F2');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->freezePane('A2');
    }
}
