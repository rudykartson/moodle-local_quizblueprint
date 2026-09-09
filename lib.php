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
 * Library callbacks for local_quizblueprint.
 *
 * Provides two upgrade-safe integration points (no core hacks):
 *   1. A node in the quiz's settings navigation.
 *   2. A "Blueprint Import" button injected into the Questions-page toolbar
 *      (next to Repaginate | Select multiple items) using the legacy
 *      before_standard_top_of_body_html callback, which is honoured on
 *      Moodle 4.3 / 4.4. On 4.5+/5.x the hook system also dispatches this
 *      callback for backwards compatibility.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a "Blueprint Import" link to the quiz module settings navigation.
 *
 * Uses the standard settings_navigation API so the link appears in the
 * quiz's "More" / settings menu for any user with the manage capability.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_quizblueprint_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

    // Only relevant inside a quiz module context.
    if (!$PAGE->cm || $PAGE->cm->modname !== 'quiz') {
        return;
    }
    if (!($context instanceof context_module)) {
        return;
    }
    if (!has_capability('local/quizblueprint:manage', $context)) {
        return;
    }

    // Find the quiz module settings node to attach our link to.
    $modulenode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
    if (!$modulenode) {
        return;
    }

    $url = new moodle_url('/local/quizblueprint/index.php', ['cmid' => $PAGE->cm->id]);
    $node = navigation_node::create(
        get_string('blueprintimport', 'local_quizblueprint'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_quizblueprint',
        new pix_icon('i/import', '')
    );
    $modulenode->add_node($node);
}

/**
 * Inject the "Blueprint Import" button into the quiz Questions-page toolbar.
 *
 * The quiz editing toolbar (Repaginate | Select multiple items) has no public
 * server-side extension API, so we add a button client-side. The JavaScript is
 * inlined via $PAGE->requires->js_amd_inline() (no build step required) and is
 * scoped strictly to the mod-quiz-edit page and to users who hold the
 * capability, so it has no effect anywhere else.
 *
 * @return string HTML to inject (empty in all cases; work is done via JS).
 */

function local_quizblueprint_before_standard_top_of_body_html() {
    global $PAGE;

    if (!isset($PAGE->pagetype) || $PAGE->pagetype !== 'mod-quiz-edit') {
        return '';
    }
    if (empty($PAGE->cm) || $PAGE->cm->modname !== 'quiz') {
        return '';
    }

    $context = context_module::instance($PAGE->cm->id);
    if (!has_capability('local/quizblueprint:manage', $context)) {
        return '';
    }

    $url = (new moodle_url('/local/quizblueprint/index.php', ['cmid' => $PAGE->cm->id]))->out(false);
    $label = get_string('blueprintimport', 'local_quizblueprint');

    $PAGE->requires->js_call_amd('local_quizblueprint/blueprintimport', 'init', [$url, $label]);

    return '';
}

// function local_quizblueprint_before_standard_top_of_body_html() {
//     global $PAGE;

//     if (!isset($PAGE->pagetype) || $PAGE->pagetype !== 'mod-quiz-edit') {
//         return '';
//     }
//     if (empty($PAGE->cm) || $PAGE->cm->modname !== 'quiz') {
//         return '';
//     }

//     $context = context_module::instance($PAGE->cm->id);
//     if (!has_capability('local/quizblueprint:manage', $context)) {
//         return '';
//     }

//     $url = (new moodle_url('/local/quizblueprint/index.php', ['cmid' => $PAGE->cm->id]))->out(false);
//     $label = get_string('blueprintimport', 'local_quizblueprint');

//     // Small, dependency-free script. It locates the toolbar that holds the
//     // Repaginate / Select-multiple controls and appends a matching button.
//     $js = <<<'JS'
// (function() {
//     function insertButton() {
//         if (document.getElementById('local-quizblueprint-btn')) {
//             return true;
//         }
//         // The repaginate and select-multiple controls share a container on the
//         // quiz edit page. Try the known anchors, falling back gracefully.
//         var anchor = document.querySelector('.repaginatecommand')
//                   || document.getElementById('repaginatecommand')
//                   || document.querySelector('.selectmultiplecommand')
//                   || document.querySelector('.edit-toolbar')
//                   || document.querySelector('.mod_quiz_edit_forms');
//         if (!anchor) {
//             return false;
//         }
//         var container = anchor.closest('.edit-toolbar') || anchor.parentNode;
//         if (!container) {
//             return false;
//         }
//         var wrap = document.createElement('span');
//         wrap.className = 'blueprintimportcommand';
//         wrap.style.marginInlineStart = '0.5rem';
//         var a = document.createElement('a');
//         a.id = 'local-quizblueprint-btn';
//         a.href = {$this_safe_url};
//         a.className = 'btn btn-secondary';
//         a.textContent = {$this_safe_label};
//         wrap.appendChild(a);
//         container.appendChild(wrap);
//         return true;
//     }
//     if (!insertButton()) {
//         // The toolbar can render slightly after page load; observe briefly.
//         var tries = 0;
//         var iv = setInterval(function() {
//             tries++;
//             if (insertButton() || tries > 40) {
//                 clearInterval(iv);
//             }
//         }, 150);
//     }
// })();
// JS;

//     // Inject the URL and label as JSON literals to avoid any escaping issues.
//     $js = str_replace(
//         ['{$this_safe_url}', '{$this_safe_label}'],
//         [json_encode($url), json_encode($label)],
//         $js
//     );

//     $PAGE->requires->js_amd_inline($js);

//     return '';
// }
