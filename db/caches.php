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
 * Cache definitions for local_quizblueprint.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Holds the absolute path of the temp .xlsx a teacher uploaded, keyed by
    // cmid, between the PREVIEW step and the CREATE (confirm) step. Session
    // scoped: it must not survive the user's session and must not leak
    // between users, which is exactly what MODE_SESSION guarantees.
    'blueprintpath' => [
        'mode' => cache_store::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600,
    ],
];