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
 * Short answer question renderer class.
 *
 * @package	qtype
 * @subpackage canvas
 * @copyright  2012 Martin Vögeli (Voma) {@link http://moodle.ch/}, based on 2009 The Open University
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Generates the output for short answer questions.
 *
 * @copyright  2009 The Open University
 * @license	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_canvas_renderer extends qtype_renderer {

	protected  function strstr_after($haystack, $needle, $case_insensitive = false) {
		$strpos = ($case_insensitive) ? 'stripos' : 'strpos';
		$pos = $strpos($haystack, $needle);
		if (is_int($pos)) {
			return substr($haystack, $pos + strlen($needle));
		}
		// Most likely false or null
		return $pos;
	}

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

		$question = $qa->get_question();
		
		$currentAnswer = $qa->get_last_qt_var('answer');

		$inputname = $qa->get_qt_field_name('answer');
		$inputattributes = array(
			'type' => 'text',
			'name' => $inputname,
			'value' => $currentAnswer,
			'id' => $inputname,
			'size' => 80,
		);

		/* Voma Start */
  
		
		if ($options->correctness) {
			// Beginning of dataURL string: "data:image/png;base64,"
			
			$correctAnswer = reset($question->answers)->answer;
			$correctAnswerData = base64_decode(self::strstr_after($correctAnswer, 'base64,'));
			$currentAnswerData = base64_decode(self::strstr_after($currentAnswer , 'base64,'));
			
			$correctAnswerImg =  imagecreatefromstring($correctAnswerData);
			$currentAnswerImg =  imagecreatefromstring($currentAnswerData);
			
			
			$blendedImg = imagecreatefromstring($currentAnswerData);
			
			imagealphablending( $correctAnswerImg, false );
			imagesavealpha( $correctAnswerImg, true );
			
			imagealphablending( $currentAnswerImg, false );
			imagesavealpha( $currentAnswerImg, true );
			
			imagealphablending( $blendedImg, false );
			imagesavealpha( $blendedImg, true );
			
			$width = imagesx($blendedImg);
			$height = imagesy($blendedImg);
			$green = imagecolorallocate($blendedImg, 0, 255, 0);
			$blue = imagecolorallocate($blendedImg, 0, 0, 255);
			$red = imagecolorallocate($blendedImg, 255, 0, 0);
			
			$matchingPixels = 0;
			$totalPixels = 0;
			for ($x = 0; $x < $width; $x++) {
				for ($y = 0; $y < $height; $y++) {
					$rgbCorrectAns = imagecolorat($correctAnswerImg, $x, $y);
					$rgbCurrentAns = imagecolorat($currentAnswerImg, $x, $y);
					$rgbCorrectAnsArray = array(($rgbCorrectAns >> 16) & 0xFF, ($rgbCorrectAns >> 8) & 0xFF, $rgbCorrectAns & 0xFF);
					$rgbCurrentAnsArray = array(($rgbCurrentAns >> 16) & 0xFF, ($rgbCurrentAns >> 8) & 0xFF, $rgbCurrentAns & 0xFF);
					if ($rgbCorrectAnsArray[2] == 255 && $rgbCurrentAnsArray[2] == 255) {
						$matchingPixels++;
						$totalPixels++;
						imagesetpixel($blendedImg, $x, $y, $green);
					} else if ($rgbCorrectAnsArray[2] == 255 && $rgbCurrentAnsArray[2] == 0) {
						imagesetpixel($blendedImg, $x, $y, $blue);
						$totalPixels++;
					} else if ($rgbCorrectAnsArray[2] == 0 && $rgbCurrentAnsArray[2] == 255) {
						imagesetpixel($blendedImg, $x, $y, $red);
						$matchingPixels--;
					}
						
				}
			}
			
			$matchPercentage = ($matchingPixels / $totalPixels)*100;
			ob_start();
			imagepng($blendedImg);
			$blendedImgData = ob_get_contents();
			ob_end_clean();
			$blendedImgDataURL = 'data:image/png;base64,' . base64_encode($blendedImgData);
			
			imagedestroy($correctAnswerImg);
			imagedestroy($currentAnswerImg);
			imagedestroy($blendedImg);
			
			$this->page->requires->yui_module('moodle-qtype_canvas-form',
					'Y.Moodle.qtype_canvas.form.init', array($question->id, $question->radius, $blendedImgDataURL));
			
		} else {

			$this->page->requires->yui_module('moodle-qtype_canvas-form',
					'Y.Moodle.qtype_canvas.form.init', array($question->id, $question->radius));
		}
// 		// Prepare some variables
// 		$temp1; 
// 		$temp2; // temporary variables
		
// 		$strID = str_replace ("answer", "", $inputattributes['id']);
		
// 		$temp1 = $question->answers;
// 		$temp2 = reset($temp1); 
		
// 		$strSolution = $temp2->answer;
// 		$temp2 = next($temp1); 
// 		$strURL = $temp2->answer;
// 		$temp2 = next($temp1); 
// 		$intRadius = $temp2->answer;
// 		/* Voma End */

		if ($options->readonly) {
			$inputattributes['readonly'] = 'readonly';
		}

		$feedbackimg = '';
		if ($options->correctness) {
			$fraction = ($matchPercentage /  ($question->threshold*5+50));
			$inputattributes['class'] = $this->feedback_class($fraction);
			$feedbackimg = $this->feedback_image($fraction);
		}

		$questiontext = $question->format_questiontext($qa);
		$placeholder = false;
		if (preg_match('/_____+/', $questiontext, $matches)) {
			$placeholder = $matches[0];
			$inputattributes['size'] = round(strlen($placeholder) * 1.1);
		}

        $bgimageArray = self::get_url_for_image($qa, 'qtype_canvas_image_file');
        
        $canvas = "<div class=\"qtype_canvas_id_" . $question->id . "\">";
        if ($options->correctness) {
        	$canvas .= "<h1>".sprintf('%0.2f', $matchPercentage)."% out of necessary ".sprintf('%0.2f', $question->threshold*5+50)."%.</h1><hr>" . $feedbackimg . "<hr>";
        } else {
        	$canvas .= "<textarea class=\"qtype_canvas_textarea\" name=\"$inputname\" id=\"qtype_canvas_textarea_id_".$question->id."\" rows=20 cols=50>$currentAnswer</textarea>";
        }
        $canvas .= "<canvas class=\"qtype_canvas\" width=\"".$bgimageArray[1]."\" height=\"".$bgimageArray[2]."\"style=\"background:url('$bgimageArray[0]')\"></canvas></div>";
        
		//$input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;
        
        

// 		if ($placeholder) {
// 			$questiontext = substr_replace($questiontext, $input,
// 					strpos($questiontext, $placeholder), strlen($placeholder));
// 		}

		$result = html_writer::tag('div', $questiontext . $canvas, array('class' => 'qtext'));

// 		if (!$placeholder) {
// 			$result .= html_writer::start_tag('div', array('class' => 'ablock'));
// 			$result .= get_string('answer', 'qtype_canvas',
// 					html_writer::tag('div', $input, array('class' => 'answer')));
// 			/* Voma Start */
// 			// Write hidden field Solution
// 			// if(strpos($_SERVER["PHP_SELF"], "review.php")){
// 				$temp1 = array('id' => $strID.'solution', 'name' => $strID.'solution', 'type' => 'hidden', 'value' => $strSolution);
// 				$result .= html_writer::empty_tag('input', $temp1);
// 			// }
// 			// Write hidden field URL
// 			$temp1 = array('id' => $strID.'url', 'name' => $strID.'url', 'type' => 'hidden', 'value' => $strURL);
// 			$result .= html_writer::empty_tag('input', $temp1);
// 			// Write hidden field Radius
// 			$temp1 = array('id' => $strID.'radius', 'name' => $strID.'radius', 'type' => 'hidden', 'value' => $intRadius);
// 			$result .= html_writer::empty_tag('input', $temp1);
// 			// Write DIV for canvas
// 			$result .= html_writer::start_tag('div', array('class' => 'qtype_canvas', 'id' => $strID));
// 			$result .= html_writer::end_tag('div');
// 			/* Voma End */
// 			$result .= html_writer::end_tag('div');
// 		}

		if ($qa->get_state() == question_state::$invalid) {
			$result .= html_writer::nonempty_tag('div',
					$question->get_validation_error(array('answer' => $currentAnswer)),
					array('class' => 'validationerror'));
		}
		return $result;
	}

	public function specific_feedback(question_attempt $qa) {
		$question = $qa->get_question();

		$answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
		if (!$answer || !$answer->feedback) {
			return '';
		}

		return $question->format_text($answer->feedback, $answer->feedbackformat,
				$qa, 'question', 'answerfeedback', $answer->id);
	}

	public function correct_response(question_attempt $qa) {
		$question = $qa->get_question();

		$answer = $question->get_matching_answer($question->get_correct_response());
		if (!$answer) {
			return '';
		}

		return get_string('correctansweris', 'qtype_canvas', s($answer->answer));
	}




    protected static function get_url_for_image(question_attempt $qa, $filearea, $itemid = 0) {
        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();
        $draftfiles = $fs->get_area_files($question->contextid, $componentname,
                $filearea, $question->id, 'id');
        if ($draftfiles) {
            foreach ($draftfiles as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $url = moodle_url::make_pluginfile_url($question->contextid, $componentname,
                		$filearea, "$qubaid/$slot/$question->id", '/',
                		$file->get_filename());
                $image = imagecreatefromstring($file->get_content());
                $width = imagesx($image);
                $height = imagesy($image);
                return array($url->out(), $width, $height);
            }
        }
        return null;
    }







}
