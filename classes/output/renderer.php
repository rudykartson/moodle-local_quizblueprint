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
 * Renderer for the Quiz Blueprint Builder pages.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_quizblueprint\output;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use stdClass;
use local_quizblueprint\local\blueprint_row;

/**
 * Renderer for the Quiz Blueprint Builder pages.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the landing page (download template + upload form).
     *
     * @param moodle_url $templateurl signed URL to template.php.
     * @param string $uploadformhtml rendered upload form.
     * @return string
     */
    public function render_landing(moodle_url $templateurl, string $uploadformhtml): string {
        $out = '';

        // Step 1: download template.
        $out .= html_writer::tag('h3', get_string('step_download', 'local_quizblueprint'));
        $out .= html_writer::tag('p',
            get_string('downloadtemplate_help', 'local_quizblueprint'));
        $out .= html_writer::link($templateurl,
            get_string('downloadtemplate', 'local_quizblueprint'),
            ['class' => 'btn btn-primary mb-4']);

        // Step 2: upload.
        $out .= html_writer::tag('h3', get_string('step_upload', 'local_quizblueprint'),
            ['class' => 'mt-4']);
        $out .= $uploadformhtml;

        return $out;
    }

    /**
     * Render a validation error box.
     *
     * @param string[] $errors list of validation error messages.
     * @return string
     */
    public function render_validation_errors(array $errors): string {
        $items = '';
        foreach ($errors as $error) {
            $items .= html_writer::tag('li', s($error));
        }
        $list = html_writer::tag('ul', $items, ['class' => 'mb-0']);
        $body = html_writer::tag('strong',
            get_string('err_validationfailed', 'local_quizblueprint')) . $list;
        return html_writer::div($body, 'alert alert-danger', ['role' => 'alert']);
    }

    /**
     * Render the preview of the structure that will be created.
     *
     * @param blueprint_row[] $rows validated rows.
     * @param moodle_url $confirmurl action URL for the confirm form.
     * @param int $cmid course module id of the target quiz.
     * @param int $existingquestions number of questions already in the quiz.
     * @param int $existingsections number of sections already in the quiz.
     * @return string
     */
    public function render_preview(array $rows, moodle_url $confirmurl, int $cmid,
            int $existingquestions = 0, int $existingsections = 0): string {
        $out = '';
        $out .= html_writer::tag('h3', get_string('step_preview', 'local_quizblueprint'));

        // Warn if the quiz already has content -- the import adds to it, not replaces.
        if ($existingquestions > 0) {
            $out .= html_writer::div(
                get_string('warn_existingcontent', 'local_quizblueprint', (object) [
                    'questions' => $existingquestions,
                    'sections' => $existingsections,
                ]),
                'alert alert-warning');
        }

        $out .= html_writer::div(
            get_string('preview_nochanges', 'local_quizblueprint'),
            'alert alert-info');

        $totalquestions = 0;
        $totalmarks = 0.0;

        foreach ($rows as $index => $row) {
            $totalquestions += $row->questioncount;
            $totalmarks += $row->questioncount * $row->markperquestion;

            $title = get_string('preview_section', 'local_quizblueprint') . ' ' .
                ($index + 1) . ': ' . s($row->sectionname);

            $details = '';
            $details .= $this->detail_line('preview_category', s($row->categoryraw) .
                ' (ID ' . $row->categoryid . ')');
            $details .= $this->detail_line('preview_randomquestions', $row->questioncount);
            $details .= $this->detail_line('preview_marks', format_float($row->markperquestion, -1));
            $details .= $this->detail_line('preview_shuffle',
                $row->shuffle ? get_string('yes') : get_string('no'));

            $card = html_writer::tag('div',
                html_writer::tag('div', $title, ['class' => 'card-header font-weight-bold']) .
                html_writer::tag('div', html_writer::tag('ul', $details,
                    ['class' => 'list-unstyled mb-0']), ['class' => 'card-body']),
                ['class' => 'card mb-3']);
            $out .= $card;
        }

        // Summary.
        $summarybody = html_writer::tag('p',
            get_string('preview_totalquestions', 'local_quizblueprint', $totalquestions)) .
            html_writer::tag('p',
            get_string('preview_totalmarks', 'local_quizblueprint',
                format_float($totalmarks, -1)));
        $out .= html_writer::tag('div',
            html_writer::tag('div', get_string('preview_summary', 'local_quizblueprint'),
                ['class' => 'card-header font-weight-bold']) .
            html_writer::tag('div', $summarybody, ['class' => 'card-body']),
            ['class' => 'card mb-3']);

        $out .= html_writer::div(get_string('note_pagebreak', 'local_quizblueprint'),
            'text-muted small mb-3');

        // Confirm form (POST + sesskey).
        $hidden = html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'cmid', 'value' => $cmid]) .
            html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'action', 'value' => 'create']) .
            html_writer::empty_tag('input',
                ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $confirmbtn = html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('confirmcreate', 'local_quizblueprint'),
        ]);
        $cancel = html_writer::link(
            new moodle_url('/local/quizblueprint/index.php', ['cmid' => $cmid]),
            get_string('cancel', 'local_quizblueprint'),
            ['class' => 'btn btn-secondary ml-2']);
        $out .= html_writer::tag('form', $hidden . $confirmbtn . $cancel,
            ['method' => 'post', 'action' => $confirmurl->out(false)]);

        return $out;
    }

    /**
     * Render the success result.
     *
     * @param stdClass $summary object with sections and slots counts.
     * @param moodle_url $backurl URL to return to the quiz.
     * @return string
     */
    public function render_result(stdClass $summary, moodle_url $backurl): string {
        $out = $this->output->notification(
            get_string('import_success', 'local_quizblueprint'), 'notifysuccess');
        $out .= html_writer::tag('p',
            get_string('import_summary', 'local_quizblueprint', $summary));
        $out .= html_writer::link($backurl,
            get_string('backtoquiz', 'local_quizblueprint'),
            ['class' => 'btn btn-primary']);
        return $out;
    }

    /**
     * Helper: a labelled detail list item.
     *
     * @param string $stringkey lang key for the label.
     * @param mixed $value already-escaped or scalar value.
     * @return string
     */
    protected function detail_line(string $stringkey, $value): string {
        return html_writer::tag('li',
            html_writer::tag('strong',
                get_string($stringkey, 'local_quizblueprint') . ': ') . $value);
    }
}