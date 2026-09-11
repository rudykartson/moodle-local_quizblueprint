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
 * Plugin version and metadata for local_quizblueprint.
 *
 * @package    local_quizblueprint
 * @copyright  2026 BharatBenz Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_quizblueprint';
$plugin->version   = 2026091100;        // YYYYMMDDXX.
$plugin->requires  = 2023100900;        // Moodle 4.3.0. Tested target: 4.3 / 4.4 / 5.x.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v1.0.0';

// We rely on mod_quiz structure APIs (mod_quiz\structure, quiz_settings, grade_calculator).
$plugin->dependencies = [
    'mod_quiz' => 2023100900,
];
