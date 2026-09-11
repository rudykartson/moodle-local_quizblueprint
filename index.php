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
 * Quiz Blueprint Builder main page: download template, upload, preview, create.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_quizblueprint\form\upload_form;
use local_quizblueprint\local\excel_parser;
use local_quizblueprint\local\validator;
use local_quizblueprint\local\category_helper;
use local_quizblueprint\local\blueprint_builder;

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Resolve cm/course/context and enforce access.
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('local/quizblueprint:manage', $context);

$pageurl = new moodle_url('/local/quizblueprint/index.php', ['cmid' => $cmid]);
$editurl = new moodle_url('/mod/quiz/edit.php', ['cmid' => $cmid]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_quizblueprint'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('blueprintimport', 'local_quizblueprint'), $pageurl);

/** @var \local_quizblueprint\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_quizblueprint');

$uploadform = new upload_form($pageurl, ['cmid' => $cmid]);

// Session-scoped store for the temp upload path between the PREVIEW and
// CREATE steps. Uses MUC (db/caches.php) instead of $SESSION directly.
$pathcache = \cache::make('local_quizblueprint', 'blueprintpath');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading_main', 'local_quizblueprint'));

// ---------------------------------------------------------------------------
// Action: CREATE (confirmed) — build the quiz structure transactionally.
// ---------------------------------------------------------------------------
if ($action === 'create' && confirm_sesskey()) {
    $storedpath = $pathcache->get($cmid) ?: null;

    if (!$storedpath || !file_exists($storedpath)) {
        echo $OUTPUT->notification(get_string('err_noupload', 'local_quizblueprint'), 'notifyproblem');
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
        echo $OUTPUT->footer();
        exit;
    }

    // Re-parse and re-validate at confirm time (defends against stale state).
    $cathelper = new category_helper($context);
    $parsed = (new excel_parser())->parse($storedpath);
    $validation = (new validator($cathelper))->validate($parsed);

    if (!empty($validation['errors'])) {
        echo $renderer->render_validation_errors($validation['errors']);
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
        echo $OUTPUT->footer();
        exit;
    }

    // Refuse to build on a quiz that already has attempts.
    $status = blueprint_builder::inspect($cmid);
    if (!$status->canedit) {
        echo $OUTPUT->notification(
            get_string('err_quizhasattempts', 'local_quizblueprint'), 'notifyproblem');
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
        echo $OUTPUT->footer();
        exit;
    }

    try {
        $builder = new blueprint_builder($cmid);
        $summary = $builder->build($validation['rows']);

        // Log the import via the Events API.
        $event = \local_quizblueprint\event\blueprint_imported::create([
            'context' => $context,
            'other' => [
                'sections' => $summary->sections,
                'slots' => $summary->slots,
                'rows' => count($validation['rows']),
            ],
        ]);
        $event->trigger();

        // Clean up the temporary file and the cached path.
        @unlink($storedpath);
        $pathcache->delete($cmid);

        echo $renderer->render_result($summary, $editurl);
    } catch (\Throwable $e) {
        // The builder rolled everything back; report and let the teacher retry.
        echo $OUTPUT->notification(
            get_string('err_buildfailed', 'local_quizblueprint', $e->getMessage()),
            'notifyproblem');
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
    }

    echo $OUTPUT->footer();
    exit;
}

// ---------------------------------------------------------------------------
// Action: PREVIEW — the upload form was submitted.
// ---------------------------------------------------------------------------
if ($data = $uploadform->get_data()) {
    // Persist the uploaded file to a temp location for the confirm step.
    $tmpfile = $uploadform->save_temp_file('blueprintfile');
    $dir = make_temp_directory('local_quizblueprint');
    $storedpath = $dir . '/bp_' . $USER->id . '_' . $cmid . '.xlsx';
    @copy($tmpfile, $storedpath);
    $pathcache->set($cmid, $storedpath);

    try {
        $cathelper = new category_helper($context);
        $parsed = (new excel_parser())->parse($storedpath);
        $validation = (new validator($cathelper))->validate($parsed);
    } catch (\moodle_exception $e) {
        echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
        echo $OUTPUT->footer();
        exit;
    }

    if (!empty($validation['errors'])) {
        echo $renderer->render_validation_errors($validation['errors']);
        echo $renderer->render_landing(
            new moodle_url('/local/quizblueprint/template.php',
                ['cmid' => $cmid, 'sesskey' => sesskey()]),
            $uploadform->render());
    } else {
        $status = blueprint_builder::inspect($cmid);
        if (!$status->canedit) {
            echo $OUTPUT->notification(
                get_string('err_quizhasattempts', 'local_quizblueprint'), 'notifyproblem');
            echo $renderer->render_landing(
                new moodle_url('/local/quizblueprint/template.php',
                    ['cmid' => $cmid, 'sesskey' => sesskey()]),
                $uploadform->render());
        } else {
            echo $renderer->render_preview($validation['rows'], $pageurl, $cmid,
                $status->questioncount, $status->sectioncount);
        }
    }

    echo $OUTPUT->footer();
    exit;
}

// ---------------------------------------------------------------------------
// Default: landing page.
// ---------------------------------------------------------------------------
$templateurl = new moodle_url('/local/quizblueprint/template.php',
    ['cmid' => $cmid, 'sesskey' => sesskey()]);
echo $renderer->render_landing($templateurl, $uploadform->render());

echo $OUTPUT->footer();