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
 * Streams the dynamically generated Excel blueprint template.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_quizblueprint\local\template_generator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$cmid = required_param('cmid', PARAM_INT);

// Standard access pattern: resolve cm/course/context, enforce login + sesskey + capability.
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_sesskey();
require_capability('local/quizblueprint:manage', $context);

$generator = new template_generator($context);
$spreadsheet = $generator->build();

$filename = clean_filename('quiz_blueprint_' . $cm->instance . '.xlsx');

// Send download headers and stream the workbook.
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
