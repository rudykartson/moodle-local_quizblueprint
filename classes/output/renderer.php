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

use moodle_url;
use plugin_renderer_base;
use stdClass;
use local_quizblueprint\local\blueprint_row;

/**
 * Renderer for the Quiz Blueprint Builder pages.
 *
 * All markup lives in Mustache templates under templates/. This class is
 * responsible only for assembling the templatedata arrays; it contains no
 * inline HTML.
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
        $data = [
            'templateurl' => $templateurl->out(false),
            'downloadtemplatelabel' => get_string('downloadtemplate', 'local_quizblueprint'),
            'downloadtemplatehelp' => get_string('downloadtemplate_help', 'local_quizblueprint'),
            'stepdownload' => get_string('step_download', 'local_quizblueprint'),
            'stepupload' => get_string('step_upload', 'local_quizblueprint'),
            'uploadformhtml' => $uploadformhtml,
        ];

        return $this->render_from_template('local_quizblueprint/landing', $data);
    }

    /**
     * Render a validation error box.
     *
     * @param string[] $errors list of validation error messages.
     * @return string
     */
    public function render_validation_errors(array $errors): string {
        $data = [
            'heading' => get_string('err_validationfailed', 'local_quizblueprint'),
            'errors' => array_map(static function(string $error): array {
                return ['message' => $error];
            }, $errors),
        ];

        return $this->render_from_template('local_quizblueprint/validation_errors', $data);
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

        $totalquestions = 0;
        $totalmarks = 0.0;
        $sections = [];

        foreach ($rows as $index => $row) {
            $totalquestions += $row->questioncount;
            $totalmarks += $row->questioncount * $row->markperquestion;

            $sections[] = [
                'title' => get_string('preview_section', 'local_quizblueprint') . ' ' .
                    ($index + 1) . ': ' . $row->sectionname,
                'details' => [
                    $this->detail(
                        'preview_category',
                        $row->categoryraw . ' (ID ' . $row->categoryid . ')'
                    ),
                    $this->detail('preview_randomquestions', $row->questioncount),
                    $this->detail('preview_marks', format_float($row->markperquestion, -1)),
                    $this->detail('preview_shuffle',
                        $row->shuffle ? get_string('yes') : get_string('no')),
                ],
            ];
        }

        $data = [
            'stepPreview' => get_string('step_preview', 'local_quizblueprint'),
            'hasexistingcontent' => $existingquestions > 0,
            'existingcontentwarning' => $existingquestions > 0
                ? get_string('warn_existingcontent', 'local_quizblueprint', (object) [
                    'questions' => $existingquestions,
                    'sections' => $existingsections,
                ])
                : '',
            'nochangesnotice' => get_string('preview_nochanges', 'local_quizblueprint'),
            'sections' => $sections,
            'summarylabel' => get_string('preview_summary', 'local_quizblueprint'),
            'totalquestionstext' => get_string('preview_totalquestions', 'local_quizblueprint',
                $totalquestions),
            'totalmarkstext' => get_string('preview_totalmarks', 'local_quizblueprint',
                format_float($totalmarks, -1)),
            'pagebreaknote' => get_string('note_pagebreak', 'local_quizblueprint'),
            'confirmurl' => $confirmurl->out(false),
            'cmid' => $cmid,
            'sesskey' => sesskey(),
            'confirmlabel' => get_string('confirmcreate', 'local_quizblueprint'),
            'cancelurl' => (new moodle_url('/local/quizblueprint/index.php',
                ['cmid' => $cmid]))->out(false),
            'cancellabel' => get_string('cancel', 'local_quizblueprint'),
        ];

        return $this->render_from_template('local_quizblueprint/preview', $data);
    }

    /**
     * Render the success result.
     *
     * @param stdClass $summary object with sections and slots counts.
     * @param moodle_url $backurl URL to return to the quiz.
     * @return string
     */
    public function render_result(stdClass $summary, moodle_url $backurl): string {
        $data = [
            'successmessage' => get_string('import_success', 'local_quizblueprint'),
            'summarytext' => get_string('import_summary', 'local_quizblueprint', $summary),
            'backurl' => $backurl->out(false),
            'backlabel' => get_string('backtoquiz', 'local_quizblueprint'),
        ];

        return $this->render_from_template('local_quizblueprint/result', $data);
    }

    /**
     * Helper: build a labelled detail entry for the preview template.
     *
     * @param string $stringkey lang key for the label.
     * @param mixed $value scalar value (auto-escaped by the Mustache engine).
     * @return array{label: string, value: mixed}
     */
    protected function detail(string $stringkey, $value): array {
        return [
            'label' => get_string($stringkey, 'local_quizblueprint'),
            'value' => $value,
        ];
    }
}