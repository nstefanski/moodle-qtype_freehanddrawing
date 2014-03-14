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
 * Question type class for the freehanddrawing question type.
 *
 * @package    qtype
 * @subpackage freehanddrawing
 * @copyright  ETHZ LET <jacob.shapiro@let.ethz.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/freehanddrawing/question.php');
require_once (dirname(__FILE__) . '/renderer.php');

/**
 * The freehanddrawing question type.
 *
 * @copyright  ETHZ LET <jacob.shapiro@let.ethz.ch> 
 * @license    http://opensource.org/licenses/BSD-3-Clause
 */
class qtype_freehanddrawing extends question_type {
    public function extra_question_fields() {
        return array('qtype_freehanddrawing', 'threshold', 'radius');
    }

    public function questionid_column_name() {
        return 'questionid';
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
    }

    public function save_question_options($question) {
        global $DB, $USER;
        $result = new stdClass();

        $context = $question->context;
        
        $parentresult = parent::save_question_options($question);
        
        if ($parentresult !== null) {
            // Parent function returns null if all is OK
            return $parentresult;
        }

        $this->save_hints($question);
        
        // Delete any left over old answer records.
        $oldanswers = $DB->get_records('question_answers', array('question' => $question->id), 'id ASC');
        foreach ($oldanswers as $oldanswer) {
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }
		// Save the new answer:
        $answer = new stdClass();
        $answer->question = $question->id;
        $answer->answer = $question->qtype_freehanddrawing_textarea_id_0;
        $answer->feedback = '';
        $answer->id = $DB->insert_record('question_answers', $answer);
        
        // Save the background image:
        
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $question->qtype_freehanddrawing_image_file, 'id');
        if (count($draftfiles) >= 2) {
        	$fs->delete_area_files( $question->context->id, 'qtype_freehanddrawing', 'qtype_freehanddrawing_image_file', $question->id);
        	file_save_draft_area_files($question->qtype_freehanddrawing_image_file, $question->context->id, 'qtype_freehanddrawing', 'qtype_freehanddrawing_image_file', $question->id, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
        } else {
        	// No files have been indicated to be uploaded. Check if this is an attempt to make a duplicate copy of this question (and that this is not a simple EDIT, in which case we don't have to do anything to the background image file): 
        	if (property_exists($question, 'pre_existing_question_id') && $question->pre_existing_question_id != 0 && $question->pre_existing_question_id != $question->id) {
        		// Yes, this was an edit form which turned out to be a "Make copy", so we need to copy over the background image of the old question into a new record:
        		// First fetch the old one:
        		$oldfiles   = $fs->get_area_files($question->context->id, 'qtype_freehanddrawing', 'qtype_freehanddrawing_image_file', $question->pre_existing_question_id, 'id');
        		if (count($oldfiles) >= 2) {
        			// Files indeed exist.
        			foreach ($oldfiles as $oldfile) {
        				if ($oldfile->is_directory()) {
        					continue;
        				}
        				$newfile = array(
        						'contextid' => $question->context->id, // ID of context
        						'component' => 'qtype_freehanddrawing',     // usually = table name
        						'filearea' => 'qtype_freehanddrawing_image_file',     // usually = table name
        						'itemid' => $question->id,               // usually = ID of row in table
        						'filepath' => '/',           // any path beginning and ending in /
        						'filename' => $oldfile->get_filename()); // any filename
        				$fs->create_file_from_storedfile($newfile, $oldfile);
        				continue;
        			}
        		}
        	}
        }



    }
    

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_answers($question, $questiondata);
    }

    public function get_random_guess_score($questiondata) {
        return 0;
    }    
    

 
    
    


    
    
    
}
