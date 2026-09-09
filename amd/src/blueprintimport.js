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
 * Injects a "Blueprint Import" button into the quiz Questions-page toolbar.
 *
 * @module     local_quizblueprint/blueprintimport
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const BUTTON_ID = 'local-quizblueprint-btn';

/**
 * Try to locate the toolbar and insert the button.
 *
 * @param {string} url The import page URL.
 * @param {string} label The button label.
 * @returns {boolean} True if the button was inserted.
 */
const insertButton = (url, label) => {
    if (document.getElementById(BUTTON_ID)) {
        return true;
    }

    const anchor = document.querySelector('.repaginatecommand')
        || document.getElementById('repaginatecommand')
        || document.querySelector('.selectmultiplecommand')
        || document.querySelector('.edit-toolbar')
        || document.querySelector('.mod_quiz_edit_forms');

    if (!anchor) {
        return false;
    }

    const container = anchor.closest('.edit-toolbar') || anchor.parentNode;
    if (!container) {
        return false;
    }

    const wrap = document.createElement('span');
    wrap.className = 'blueprintimportcommand ms-2';

    const link = document.createElement('a');
    link.id = BUTTON_ID;
    link.href = url;
    link.className = 'btn btn-secondary';
    link.textContent = label;

    wrap.appendChild(link);
    container.appendChild(wrap);
    return true;
};

/**
 * Initialise the button, retrying briefly if the toolbar isn't ready yet.
 *
 * @param {string} url The import page URL.
 * @param {string} label The button label.
 */
export const init = (url, label) => {
    if (insertButton(url, label)) {
        return;
    }

    let tries = 0;
    const iv = setInterval(() => {
        tries++;
        if (insertButton(url, label) || tries > 40) {
            clearInterval(iv);
        }
    }, 150);
};