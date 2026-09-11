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
 * English strings for local_quizblueprint.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quiz Blueprint Builder';
$string['quizblueprint:manage'] = 'Build quiz structure from a blueprint spreadsheet';

// Navigation / button.
$string['blueprintimport'] = 'Blueprint Import';
$string['backtoquiz'] = 'Back to quiz editing';

// Page sections.
$string['heading_main'] = 'Quiz Blueprint Builder';
$string['step_download'] = 'Step 1 — Download template';
$string['step_upload'] = 'Step 2 — Upload completed blueprint';
$string['step_preview'] = 'Step 3 — Preview and confirm';
$string['downloadtemplate'] = 'Download template';
$string['downloadtemplate_help'] = 'Generates an Excel workbook pre-filled with the categories and question counts available to this quiz.';
$string['uploadblueprint'] = 'Upload blueprint';
$string['createstructure'] = 'Create quiz structure';
$string['confirmcreate'] = 'Confirm and create';
$string['cancel'] = 'Cancel';

// Template sheet headings.
$string['sheet_blueprint'] = 'Blueprint';
$string['sheet_categories'] = 'Category Reference';
$string['sheet_questions'] = 'Question Reference';
$string['col_sectionname'] = 'Section Name';
$string['col_category'] = 'Category';
$string['col_random'] = 'Random';
$string['col_questioncount'] = 'Question Count';
$string['col_markperquestion'] = 'Mark Per Question';
$string['col_shuffle'] = 'Shuffle';
$string['col_pagebreak'] = 'Page Break';
$string['col_availablequestions'] = 'Available Questions';
$string['col_categoryid'] = 'Category ID';
$string['col_questionname'] = 'Question Name';

// Preview labels.
$string['preview_section'] = 'Section';
$string['preview_category'] = 'Category';
$string['preview_randomquestions'] = 'Random questions';
$string['preview_marks'] = 'Marks per question';
$string['preview_shuffle'] = 'Shuffle';
$string['preview_pagebreak'] = 'Page break';
$string['preview_summary'] = 'Summary';
$string['preview_totalquestions'] = 'Total questions: {$a}';
$string['preview_totalmarks'] = 'Total marks: {$a}';
$string['preview_nochanges'] = 'No changes have been made yet. Review the structure below, then choose Confirm and create.';

// Success / results.
$string['import_success'] = 'Quiz structure created successfully.';
$string['import_summary'] = 'Created {$a->sections} section(s) and {$a->slots} random question slot(s).';

// Validation errors.
$string['err_noupload'] = 'Please choose a spreadsheet to upload.';
$string['err_cannotread'] = 'The uploaded file could not be read as a spreadsheet: {$a}';
$string['err_nosheet'] = 'The workbook does not contain a "{$a}" sheet.';
$string['err_emptyblueprint'] = 'The blueprint sheet contains no data rows.';
$string['err_row_sectionname'] = 'Row {$a}: Section Name is required.';
$string['err_row_category_missing'] = 'Row {$a}: Category is required.';
$string['err_row_category_notfound'] = 'Row {$a->row}: Category "{$a->name}" was not found among the categories available to this quiz.';
$string['err_row_category_ambiguous'] = 'Row {$a->row}: Category name "{$a->name}" is ambiguous (matches IDs: {$a->ids}). Use the numeric Category ID instead.';
$string['err_row_count_numeric'] = 'Row {$a}: Question Count must be a positive whole number.';
$string['err_row_count_exceeds'] = 'Row {$a->row}: requested {$a->requested} random question(s) but category "{$a->name}" only has {$a->available} available.';
$string['err_row_mark_numeric'] = 'Row {$a}: Mark Per Question must be a number greater than zero.';
$string['err_row_random_invalid'] = 'Row {$a}: Random must be Yes/No (also accepts Y, N, TRUE, FALSE).';
$string['err_row_nonrandom_unsupported'] = 'Row {$a}: only random-question sections are supported in this version (set Random = Yes).';
$string['err_validationfailed'] = 'The blueprint could not be imported. Please fix the following and re-upload:';
$string['err_buildfailed'] = 'Quiz structure creation failed and was rolled back. No changes were made. Details: {$a}';
$string['err_quizhasattempts'] = 'This quiz already has attempts, so its structure cannot be changed. Blueprint import is only available before any student attempts the quiz.';

// Misc.
$string['warn_existingcontent'] = 'This quiz already contains {$a->questions} question(s) across {$a->sections} section(s). Importing will ADD the sections below to the existing structure -- it does not replace what is already there.';
$string['note_pagebreak'] = 'Note: each section always begins on a new page in Moodle, so a page break is inserted between sections automatically.';
$string['privacy:metadata'] = 'The Quiz Blueprint Builder plugin does not store any personal data. Import actions are recorded by the standard Moodle logging system via events.';
$string['event_blueprint_imported'] = 'Quiz blueprint imported';

$string['event_blueprint_imported_desc'] = "The user with id '{\$a->userid}' imported a quiz blueprint into the quiz with course module id '{\$a->cmid}', creating {\$a->sections} section(s) and {\$a->slots} random question slot(s).";

$string['err_missingvendor'] = 'A required third-party library is missing from this plugin ({$a}).';