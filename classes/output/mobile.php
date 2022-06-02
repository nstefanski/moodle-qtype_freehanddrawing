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
 * Mobile output class for qtype_gapfill
 *
 * @package    qtype_freehanddrawing
 * @copyright  2022 Nick Stefanski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_freehanddrawing\output;

class mobile {

    /**
     * Returns the freehanddrawing question type for the quiz in the mobile app.
     * @return void
     */
    public static function mobile_get_freehanddrawing() {
        global $CFG;
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => file_get_contents($CFG->dirroot .'/question/type/freehanddrawing/mobile/addon-qtype-freehanddrawing.html')
                    ]
            ],
            'javascript' => file_get_contents($CFG->dirroot . '/question/type/freehanddrawing/mobile/mobile.js')
        ];
    }
}
