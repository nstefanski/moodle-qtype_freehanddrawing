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
 * Upgrade library code for the canvas question type.
 *
 * @package    qtype
 * @subpackage canvas
 * @copyright  ETHZ LET <jacob.shapiro@let.ethz.ch>
 * @license    http://opensource.org/licenses/BSD-3-Clause
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Class for converting attempt data for canvas questions when upgrading
 * attempts to the new question engine.
 *
 * This class is used by the code in question/engine/upgrade/upgradelib.php.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
if ($oldversion < XXXXXXXXXX) {

    // Define table qtype_canvas to be created
    $table = new xmldb_table('qtype_canvas');

    // Adding fields to table qtype_canvas
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('threshold', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
    $table->add_field('radius', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

    // Adding keys to table qtype_canvas
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

    // Conditionally launch create table for qtype_canvas
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    // canvas savepoint reached
    upgrade_plugin_savepoint(true, XXXXXXXXXX, 'qtype', 'canvas');
}
*/


class qtype_canvas_qe2_attempt_updater extends question_qtype_attempt_updater {
    public function right_answer() {
        foreach ($this->question->options->answers as $ans) {
            if ($ans->fraction > 0.999) {
                return $ans->answer;
            }
        }
    }

    public function was_answered($state) {
        return !empty($state->answer);
    }

    public function response_summary($state) {
        if (!empty($state->answer)) {
            return $state->answer;
        } else {
            return null;
        }
    }

    public function set_first_step_data_elements($state, &$data) {
    }

    public function supply_missing_first_step_data(&$data) {
    }

    public function set_data_elements_for_step($state, &$data) {
        if (!empty($state->answer)) {
            $data['answer'] = $state->answer;
        }
    }
}
