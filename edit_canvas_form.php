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
        global $PAGE, $CFG;
        
        
        if (array_key_exists('id', $this->question) === true) {
        	$question = $this->question;
        	if (array_key_exists('contextid', $question) === false || array_key_exists('answers', $question) === false) {
        		$question = question_bank::load_question($question->id, false);
        	}
        	// Question already exists! We are in edit mode.
        	$bgimageArray = qtype_canvas_renderer::get_image_for_question($question);
        	$canvasHTMLParams = "width=\"".$bgimageArray[1]."\" height=\"".$bgimageArray[2]."\"style=\"background:url('$bgimageArray[0]')\"";
        	
        	$eraserHTMLParams = '';
        	
        	$canvasTextAreaPreexistingAnswer = reset($question->answers)->answer;
        
        } else {
        	$canvasHTMLParams = 'style="display: none;"';
        	$eraserHTMLParams = 'style="display: none;"';
        	$canvasTextAreaPreexistingAnswer = '';
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
 
        $mform->addElement('textarea', 'qtype_canvas_textarea_id_0', get_string("introtext", "qtype_canvas"), 'class="qtype_canvas_textarea" wrap="virtual" rows="20" cols="50"');
        $mform->setDefault('qtype_canvas_textarea_id_0', $canvasTextAreaPreexistingAnswer);
        $mform->addElement('filepicker', 'qtype_canvas_image_file', get_string('file'), null,
                           array('maxbytes' => 1572864/*1.5MB*/, 'accepted_types' => array('image', 'picture')));
        $mform->closeHeaderBefore('drawsolution');
        //$mform->addElement('html', '<img ALT="Erase Canvas" SRC="'.$CFG->wwwroot . '/question/type/canvas/pix/Eraser-icon.png" CLASS="qtype_canvas_eraser" ID="qtype_canvas_eraser_id_0" '.$eraserHTMLParams.'>');

        $mform->addElement('html', '<div class="qtype_canvas_container_div" '.$eraserHTMLParams.'><img ALT="Erase Canvas" SRC="'.$CFG->wwwroot . '/question/type/canvas/pix/Eraser-icon.png" CLASS="qtype_canvas_eraser" ID="qtype_canvas_eraser_id_0" '.$eraserHTMLParams.'><canvas class="qtype_canvas" '.$canvasHTMLParams.'>');
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
        return $question;
    }

    public function validation($data, $files) {
        return ""; // TODO: Check what's necessary to do this gracefully.
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== '') {
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 ||
                    !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_canvas');
                $answercount++;
            }
        }
        if ($answercount==0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_canvas', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }
        return $errors;
    }

    public function qtype() {
        return 'canvas';
    }
}
