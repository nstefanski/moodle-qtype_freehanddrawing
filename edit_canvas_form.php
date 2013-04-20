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

/**
 * Short canvas question editing form definition.
 *
 * @copyright  2012 Martin Vögeli (Voma), based on 2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_canvas_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        /* Voma add start */
        global $PAGE;
//        $PAGE->requires->js_init_call('M.qtype_canvas.init');
		// http://docs.moodle.org/dev/Using_jQuery_with_Moodle_2.0
//		$PAGE->requires->js('/question/type/canvas/jquery-1.7.2.js');
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

       $mform->addElement('select', 'usecase',
                get_string('casesensitive', 'qtype_canvas'), array(
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
 
		/* Voma add end */
 		/* Voma exclude start
        $menu = array(
            get_string('caseno', 'qtype_canvas'),
            get_string('caseyes', 'qtype_canvas')
        );
        $mform->addElement('select', 'usecase',
                get_string('casesensitive', 'qtype_canvas'), $menu);
 		Voma exclude end */
        /* Voma start edit */
        /*$mform->addElement('static', 'drawsolution',
                get_string('correctanswers', 'qtype_canvas'),
                get_string('filloutoneanswer', 'qtype_canvas'));
        */
        $mform->addElement('filepicker', 'qtype_canvas_image_file', get_string('file'), null,
                           array('maxbytes' => $maxbytes, 'accepted_types' => '*'));
        $mform->closeHeaderBefore('drawsolution');
        /* Voma end edit */
        $mform->addElement('html', '<canvas id="qtype_canvas_bgimage" style="margin-left: auto; margin-right: auto; margin-top:5px; border: 1px solid black; cursor: crosshair; display: none;">');
        $this->add_per_answer_fields($mform, get_string('answerno', 'qtype_canvas', '{no}'),
                question_bank::fraction_options());

        $this->add_interactive_settings();

    }
    public function js_call() {
        global $PAGE;
        $params = array('nothing'=>1);
        $PAGE->requires->yui_module('moodle-qtype_canvas-form',
                'Y.Moodle.qtype_canvas.form.init');
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);
        $this->js_call();
        return $question;
    }

    public function validation($data, $files) {
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
