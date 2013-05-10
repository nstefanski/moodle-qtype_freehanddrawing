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
 * Short answer question definition class.
 *
 * @package    qtype
 * @subpackage canvas
 * @copyright  2012 Martin Vögeli (Voma) {@link http://moodle.ch/}, based on 2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once (dirname(__FILE__) . '/renderer.php');

/**
 * Represents a short answer question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_canvas_question extends question_graded_by_strategy
        implements question_response_answer_comparer {
    /** @var boolean whether answers should be graded case-sensitively. */
    public $threshold;
    public $radius;

    /** @var array of question_answer. */
    public $answers = array();

    public function __construct() {
        parent::__construct(new question_first_matching_answer_grading_strategy($this));
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) &&
                ($response['answer'] || $response['answer'] === '0');
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseenterananswer', 'qtype_canvas');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function get_answers() {
        return $this->answers;
    }

    public function compare_response_with_answer(array $response, question_answer $answer) {

    	
    	if ($answer->answer === '' || array_key_exists('answer', $response) === FALSE) {
    		return false;
    	}
    	
    	$correctAnswer = $answer->answer;
    	$currentAnswer = $response['answer'];
    	
    	
    	$correctAnswerData = base64_decode(qtype_canvas_renderer::strstr_after($correctAnswer, 'base64,'));
    	$currentAnswerData = base64_decode(qtype_canvas_renderer::strstr_after($currentAnswer , 'base64,'));
    		
    	$correctAnswerImg =  imagecreatefromstring($correctAnswerData);
    	$currentAnswerImg =  imagecreatefromstring($currentAnswerData);
    	if ($correctAnswerImg === FALSE || $currentAnswerImg === FALSE) {
    		return false;
    	}
    		
    	$width = imagesx($correctAnswerImg);
    	$height = imagesy($correctAnswerImg);
    		
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
    			} else if ($rgbCorrectAnsArray[2] == 255 && $rgbCurrentAnsArray[2] == 0) {
    				$totalPixels++;
    			} else if ($rgbCorrectAnsArray[2] == 0 && $rgbCurrentAnsArray[2] == 255) {
    				$matchingPixels--;
    			}
    	
    		}
    	}

    	imagedestroy($correctAnswerImg);
    	imagedestroy($currentAnswerImg);
    	
    	$matchPercentage = ($matchingPixels / $totalPixels)*100;
    		

    	
    	if (($this->threshold)*5+50 <= $matchPercentage) {
    		$answer->fraction = 1;
    		return true;
    	}
    	
    	$answer->fraction = 0;
    	return false;
    	
    	
	}
	/* Voma end */

    public static function compare_string_with_wildcard($string, $pattern, $ignorecase) {
        // Break the string on non-escaped asterisks.
        $bits = preg_split('/(?<!\\\\)\*/', $pattern);
        // Escape regexp special characters in the bits.
        $excapedbits = array();
        foreach ($bits as $bit) {
            $excapedbits[] = preg_quote(str_replace('\*', '*', $bit));
        }
        // Put it back together to make the regexp.
        $regexp = '|^' . implode('.*', $excapedbits) . '$|u';

        // Make the match insensitive if requested to.
        if ($ignorecase) {
            $regexp .= 'i';
        }

        return preg_match($regexp, trim($string));
    }

    public function check_file_access($qa, $options, $component, $filearea,
            $args, $forcedownload) {
        if ($component == 'qtype_canvas' && $filearea == 'qtype_canvas_image_file') {
            $question = $qa->get_question();                                                                                                                                
            $itemid = reset($args);                                                                                                                                         
            return ($itemid == $question->id);                                                                                                                            
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}
