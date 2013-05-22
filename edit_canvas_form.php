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
 * Defines the editing form for the canvas question type.
 *
 * @package    qtype
 * @subpackage canvas
 * @copyright  2012 Martin Vögeli (Voma) {@link http://moodle.ch/}, based on 2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once (dirname(__FILE__) . '/renderer.php');

/**
 * Short canvas question editing form definition.
 *
 * @copyright  2012 Martin Vögeli (Voma), based on 2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_canvas_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        global $PAGE, $CFG, $USER;
        
        
        $usercontext = context_user::instance($USER->id);
        $bgImageArray = null;
        $canvasTextAreaPreexistingAnswer = '';
        $noBackgroundImageSelectedYetStyle = '';
        $eraserHTMLParams = '';

       	$bgImageArray = qtype_canvas_renderer::get_image_for_files($usercontext->id, 'user', 'draft',  file_get_submitted_draft_itemid('qtype_canvas_image_file'));
        if ($bgImageArray !== null) {
        	$canvasHTMLParams = "width=\"".$bgImageArray[1]."\" height=\"".$bgImageArray[2]."\"style=\"background:url('$bgImageArray[0]')\"";
        	$noBackgroundImageSelectedYetStyle = 'style="display: none;"';
        } else {
        	if (array_key_exists('id', $this->question) === true) {
        		$question = $this->question;
        		if (array_key_exists('contextid', $question) === false || array_key_exists('answers', $question) === false) {
        			$question = question_bank::load_question($question->id, false);
        		}
        		// Question already exists! We are in edit mode.
        		$bgImageArray = qtype_canvas_renderer::get_image_for_question($question);
        		$canvasHTMLParams = "width=\"".$bgImageArray[1]."\" height=\"".$bgImageArray[2]."\"style=\"background:url('$bgImageArray[0]')\"";
        		
        		$noBackgroundImageSelectedYetStyle = 'style="display: none;"';
        		 
        		$canvasTextAreaPreexistingAnswer = reset($question->answers)->answer;
        		
        		
        		
        		
        		// Tried to implement this:  http://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms#Load_existing_files_into_draft_area
//   				// $bgImageArray[3] will contain the $file->get_itemid() so that we can load it up for the UI in the form.
        		$entry = new stdClass;
        		$entry->id = null;
        		file_prepare_draft_area($bgImageArray[3], $question->contextid, 'qtype_canvas', 'qtype_canvas_image_file', $question->id, array('maxbytes' => 1572864/*1.5MB*/, 'accepted_types' => array('image', 'picture')));
        		$entry->attachments = $bgImageArray[3];
        		
        		
        		
        		//$this->$mform->set_data($entry); //<-- for some reason this doesn't exist even though it's in the DOCS! seems to work only for moodleforms:: and not for QuickForm?
        		
        	
        	
        	} else {
        		$canvasHTMLParams = 'style="display: none;"';
        		$eraserHTMLParams = 'style="display: none;"';
        	}	
        }
        
        
        
        
        $mform->addElement('header', 'qtype_canvas_drawing_parameters', get_string('drawing_parameters', 'qtype_canvas'));
        $mform->addElement('select', 'radius',
                get_string('radius', 'qtype_canvas'), array(
                0 => 1,
                1 => 3,
                2 => 5,
                3 => 7,
                4 => 9,
                5 => 11,
                6 => 13,
                7 => 15,
                8 => 17,
                9 => 19));

       $mform->addElement('select', 'threshold',
                get_string('threshold', 'qtype_canvas'), array(
                0 => 50,
                1 => 55,
                2 => 60,
                3 => 65,
                4 => 70,
                5 => 75,
                6 => 80,
                7 => 85,
                8 => 90,
                9 => 95,
                10 => 100));
 

        // TODO: Implement this: http://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms#Load_existing_files_into_draft_area
        $mform->addElement('filepicker', 'qtype_canvas_image_file', get_string('file'), null,
                           array('maxbytes' => 1572864/*1.5MB*/, 'accepted_types' => array('image', 'picture')));
        $mform->closeHeaderBefore('drawsolution');
        //$mform->addElement('html', '<img ALT="Erase Canvas" SRC="'.$CFG->wwwroot . '/question/type/canvas/pix/Eraser-icon.png" CLASS="qtype_canvas_eraser" ID="qtype_canvas_eraser_id_0" '.$eraserHTMLParams.'>');
        $mform->addElement('textarea', 'qtype_canvas_textarea_id_0', get_string("drawingrawdata", "qtype_canvas"), 'class="qtype_canvas_textarea" wrap="virtual" rows="20" cols="50"');
        $mform->setDefault('qtype_canvas_textarea_id_0', $canvasTextAreaPreexistingAnswer);
        $mform->addElement('html', '<div class="fitem"><div class="fitemtitle">' . 
        		get_string("drawanswer", "qtype_canvas").'</div><div class="felement"><div class="qtype_canvas_no_background_image_selected_yet" '.$noBackgroundImageSelectedYetStyle.'>' . 
        		get_string('nobackgroundimageselectedyet', 'qtype_canvas') . 
        		'</div><div class="qtype_canvas_container_div" '.$eraserHTMLParams.'><img ALT="Erase Canvas" SRC="'.$CFG->wwwroot . '/question/type/canvas/pix/Eraser-icon.png" CLASS="qtype_canvas_eraser" ID="qtype_canvas_eraser_id_0" '.$eraserHTMLParams.'><canvas class="qtype_canvas" '.$canvasHTMLParams.'>');
        //$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_canvas', '{no}'), question_bank::fraction_options());

        $this->add_interactive_settings();


    }
    public function js_call() {
        global $PAGE;
        $params = array('nothing'=>1);
        $PAGE->requires->yui_module('moodle-qtype_canvas-form',
                'Y.Moodle.qtype_canvas.form.init', array(0, 0));
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);
        $this->js_call();
        
        
//         if (array_key_exists('id', $this->question) === true) {
//         	$question = $this->question;
//         	if (array_key_exists('contextid', $question) === false || array_key_exists('answers', $question) === false) {
//         		$question = question_bank::load_question($question->id, false);
//         	}
//         	// Question already exists! We are in edit mode.
//         	$bgImageArray = qtype_canvas_renderer::get_image_for_question($question);
        
//         	// Tried to implement this:  http://docs.moodle.org/dev/Using_the_File_API_in_Moodle_forms#Load_existing_files_into_draft_area
//         	//   				// $bgImageArray[3] will contain the $file->get_itemid() so that we can load it up for the UI in the form.
//         	//$entry = new stdClass;
//         	//$entry->id = null;
//         	//file_prepare_draft_area($bgImageArray[3], $question->contextid, 'qtype_canvas', 'qtype_canvas_image_file', $question->id, array('maxbytes' => 1572864/*1.5MB*/, 'accepted_types' => array('image', 'picture')));
//         	//$question->qtype_canvas_image_files =  $bgImageArray[3];
       
        
        	 
        	 
//         }
        
        
        
        return $question;
    }

    public function validation($data, $files) {
    	global $USER;
        $errors = parent::validation($data, $files);
        
        $bgWidth = 0;
        $bgHeight = 0;
        
        // Check that there is _any_ bg-image
        // Step 1: Prefer files given in this current form over pre-existing files (since if both exist, the new files will be the ones that get saved).
       
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['qtype_canvas_image_file'], 'id');
        if (count($draftfiles) < 2) {
        	// No files given in the form, check if they maybe pre-exist:
        	if (array_key_exists('id', $this->question) === true) {
        		$question = $this->question;
        		if (array_key_exists('contextid', $question) === false || array_key_exists('answers', $question) === false) {
        			$question = question_bank::load_question($question->id, false);
        		}
        		$oldfiles   = $fs->get_area_files($question->contextid,  'qtype_canvas', 'qtype_canvas_image_file', $question->id, 'id');
				if (count($oldfiles) < 2) {
					// We are in trouble.
					$errors["qtype_canvas_image_file"] = get_string('backgroundfilemustbegiven', 'qtype_canvas');
				} else {
					foreach ($oldfiles as $file) {
						if ($file->is_directory()) {
							continue;
						}
						$image = imagecreatefromstring($file->get_content());
						$bgWidth = imagesx($image);
						$bgHeight = imagesy($image);
						imagedestroy($image);
					}
				}
        	} else {
        		$errors["qtype_canvas_image_file"] = get_string('backgroundfilemustbegiven', 'qtype_canvas');
        	}
        } else {
        	foreach ($draftfiles as $file) {
        		if ($file->is_directory()) {
        			continue;
        		}
         		$image = imagecreatefromstring($file->get_content());
        		$bgWidth = imagesx($image);
        		$bgHeight = imagesy($image);
        		imagedestroy($image);
        	}
        }   
        // Check that there is a "drawing" by the user (=teacher):
        if ($data['qtype_canvas_textarea_id_0'] == '') {
        	$errors["qtype_canvas_textarea_id_0"] = get_string('drawingmustbegiven', 'qtype_canvas');
        } else {
        	$imgData = base64_decode(qtype_canvas_renderer::strstr_after($data['qtype_canvas_textarea_id_0'], 'base64,'));
        	$imgGDResource =  imagecreatefromstring($imgData);
        	if ($imgGDResource === FALSE) {
        		$errors["qtype_canvas_textarea_id_0"] = get_string('drawingmustbegiven', 'qtype_canvas');
        	} else {
        		// Check that it has non-zero dimensions (would've been nice to check that its dimensions fit those of the uploaded file but perhaps that is an overkill??)
        		if (imagesx($imgGDResource) != $bgWidth || imagesy($imgGDResource) != $bgHeight) {
        			$errors["qtype_canvas_textarea_id_0"] = get_string('drawingmustbegiven', 'qtype_canvas');
        		}
        		imagedestroy($imgGDResource);
        	}
        }
//         $answers = $data['answer'];
//         $answercount = 0;
//         $maxgrade = false;
//         foreach ($answers as $key => $answer) {
//             $trimmedanswer = trim($answer);
//             if ($trimmedanswer !== '') {
//                 $answercount++;
//                 if ($data['fraction'][$key] == 1) {
//                     $maxgrade = true;
//                 }
//             } else if ($data['fraction'][$key] != 0 ||
//                     !html_is_blank($data['feedback'][$key]['text'])) {
//                 $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_canvas');
//                 $answercount++;
//             }
//         }
//         if ($answercount==0) {
//             $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_canvas', 1);
//         }
//         if ($maxgrade == false) {
//             $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
//         }
        return $errors;
    }

    public function qtype() {
        return 'canvas';
    }
}
