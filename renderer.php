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

	public static  function strstr_after($haystack, $needle, $case_insensitive = false) {
		$strpos = ($case_insensitive) ? 'stripos' : 'strpos';
		$pos = $strpos($haystack, $needle);
		if (is_int($pos)) {
			return substr($haystack, $pos + strlen($needle));
		}
		// Most likely false or null
		return $pos;
	}

	public static function compare_drawings($teacherAnswer, $studentAnswer, $createBlendedImg = false) {

	//	ini_set('memory_limit', '-1');
		
		// Beginning of dataURL string: "data:image/png;base64,"
		
		$correctAnswerData = base64_decode(self::strstr_after($teacherAnswer, 'base64,'));
		$currentAnswerData = base64_decode(self::strstr_after($studentAnswer , 'base64,'));
			
		$correctAnswerImg =  imagecreatefromstring($correctAnswerData);
		$currentAnswerImg =  imagecreatefromstring($currentAnswerData);

		imagealphablending( $correctAnswerImg, false );
		imagesavealpha( $correctAnswerImg, true );
			
		imagealphablending( $currentAnswerImg, false );
		imagesavealpha( $currentAnswerImg, true );
		
		$width = imagesx($correctAnswerImg);
		$height = imagesy($correctAnswerImg);
		
		if ($createBlendedImg ===  true) {
			$blendedImg = imagecreatefromstring($currentAnswerData);
			imagealphablending( $blendedImg, false );
			imagesavealpha( $blendedImg, true );
			$green = imagecolorallocate($blendedImg, 0, 255, 0);
			$blue = imagecolorallocate($blendedImg, 0, 0, 255);
			$red = imagecolorallocate($blendedImg, 255, 0, 0);
		}
			
		$matchingPixels = 0;
		$teacherOnlyPixels = 0;
		$studentOnlyPixels = 0;
		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$rgbCorrectAns = imagecolorat($correctAnswerImg, $x, $y);
				$rgbCurrentAns = imagecolorat($currentAnswerImg, $x, $y);
				$rgbCorrectAnsArray = array(($rgbCorrectAns >> 16) & 0xFF, ($rgbCorrectAns >> 8) & 0xFF, $rgbCorrectAns & 0xFF);
				$rgbCurrentAnsArray = array(($rgbCurrentAns >> 16) & 0xFF, ($rgbCurrentAns >> 8) & 0xFF, $rgbCurrentAns & 0xFF);
				if ($rgbCorrectAnsArray[2] == 255 && $rgbCurrentAnsArray[2] == 255) {
					$matchingPixels++;
					if ($createBlendedImg ===  true) {
						imagesetpixel($blendedImg, $x, $y, $green);
					}
				} else if ($rgbCorrectAnsArray[2] == 255 && $rgbCurrentAnsArray[2] == 0) {
					$teacherOnlyPixels++;
					if ($createBlendedImg ===  true) {
						imagesetpixel($blendedImg, $x, $y, $blue);
					}
				} else if ($rgbCorrectAnsArray[2] == 0 && $rgbCurrentAnsArray[2] == 255) {
					$studentOnlyPixels++;
					if ($createBlendedImg ===  true) {
						imagesetpixel($blendedImg, $x, $y, $red);
					}
				}
			}
		}
		
		imagedestroy($correctAnswerImg);
		imagedestroy($currentAnswerImg);
		
		$matchPercentage = ($matchingPixels / ($matchingPixels + $teacherOnlyPixels + $studentOnlyPixels))*100;
		
		if ($createBlendedImg ===  true) {
			$blendedImgDataURL = self::toDataURL_from_gdImage($blendedImg);
			imagedestroy($blendedImg);
			return array($blendedImgDataURL, $matchPercentage);
		}
		return $matchPercentage;
	}
	
	public static function toDataURL_from_gdImage($gdImage) {
//		ini_set('memory_limit', '-1');
		ob_start();
		imagepng($gdImage);
		$ImgData = ob_get_contents();
		ob_end_clean();
		
		
		stream_wrapper_register("BlobDataAsFileStream", "blob_data_as_file_stream");
		
		//Store $swf_blob_data to the data stream
		blob_data_as_file_stream::$blob_data_stream = $ImgData;
		
		//Run getimagesize() on the data stream
		$image_size = getimagesize('BlobDataAsFileStream://');
		
		stream_wrapper_unregister("BlobDataAsFileStream");
		
		$ImgDataURL = 'data:' . $image_size['mime'] . ';base64,' . base64_encode($ImgData);
		return $ImgDataURL;
	}
	
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
    	
    	global $CFG;

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
			
			$correctAnswer = reset($question->answers)->answer;
	
			list($blendedImgDataURL, $matchPercentage) = self::compare_drawings($correctAnswer, $currentAnswer, true);
			
			
			
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

        $bgimageArray = self::get_image_for_question($question);
        
        $canvas = "<div class=\"qtype_canvas_id_" . $question->id . "\">";
        if ($options->correctness) {
        	$canvas .= "<h1>".sprintf('%0.2f', $matchPercentage)."% out of necessary ".sprintf('%0.2f', $question->threshold*5+50)."%.</h1><hr>" . $feedbackimg . "<hr>";
        } else {
        	$canvas .= '<img ALT="Erase Canvas" SRC="'.$CFG->wwwroot . '/question/type/canvas/pix/Eraser-icon.png" CLASS="qtype_canvas_eraser">';
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
		return '';
		$question = $qa->get_question();

		$answer = $question->get_matching_answer($question->get_correct_response());
		if (!$answer) {
			return '';
		}

		return get_string('correctansweris', 'qtype_canvas', s($answer->answer));
	}




    public static function get_image_for_question($question) {
    	$fs = get_file_storage();
    	
    	$draftfiles = $fs->get_area_files($question->contextid,  'qtype_canvas', 'qtype_canvas_image_file', $question->id, 'id');
    	if ($draftfiles) {
    		foreach ($draftfiles as $file) {
    			if ($file->is_directory()) {
    				continue;
    			}
    			// Prefer to send dataURL instead of mess with the plugin file API which turned out to be quite cumbersome. Anyway this should really speed things up for the browser
    			// as it reduces HTTP requests.
    			// ----------
    			//$url = moodle_url::make_pluginfile_url($question->contextid, $componentname, $filearea, "$qubaid/$slot/$question->id", '/', $file->get_filename()); 			
    			// ----------
    			$image = imagecreatefromstring($file->get_content());
    			$width = imagesx($image);
    			$height = imagesy($image);
    			$ImgDataURL = self::toDataURL_from_gdImage($image);
    			imagedestroy($image);
    			return array($ImgDataURL, $width, $height);
    		}
    	}
    	return null;
    }





}











// Take from http://php.net/manual/en/function.getimagesize.php
// Because I couldn't find a way to apply imagegetsize() on a raw blob of data
// instead of a filename. imagegetsize() is necessary to obtain the MIME
// type of the image.


// Le'ts hope this doesn't create too much overhead.

/*
 ----------------------------------------------------------------------
PHP Blob Data As File Stream v1.0 (C) 2012 Alex Yam <alexyam@live.com>
This code is released under the MIT License.
----------------------------------------------------------------------
[Summary]

A simple class for PHP functions to read and write blob data as a file
using a stream wrapper.

Particularly useful for running getimagesize() to get the width and
height of .SWF Flash files that are stored in the database as blob data.

Tested on PHP 5.3.10.

----------------------------------------------------------------------
[Usage Example]

//Include
include('./blob_data_as_file_stream.php');

//Register the stream wrapper
stream_wrapper_register("BlobDataAsFileStream", "blob_data_as_file_stream");

//Fetch a .SWF file from the Adobe website and store it into a variable.
//Replace this with your own fetch-swf-blob-data-from-database code.
$swf_url = 'http://www.adobe.com/swf/software/flash/about/flashAbout_info_small.swf';
$swf_blob_data = file_get_contents($swf_url);

//Store $swf_blob_data to the data stream
blob_data_as_file_stream::$blob_data_stream = $swf_blob_data;

//Run getimagesize() on the data stream
$swf_info = getimagesize('BlobDataAsFileStream://');
var_dump($swf_info);

----------------------------------------------------------------------
[Usage Output]

array(5) {
[0]=>
int(159)
[1]=>
int(91)
[2]=>
int(13)
[3]=>
string(23) "width="159" height="91""
["mime"]=>
string(29) "application/x-shockwave-flash"
}

*/

class blob_data_as_file_stream {

	private static $blob_data_position = 0;
	public static $blob_data_stream = '';

	public static function stream_open($path,$mode,$options,&$opened_path){
		static::$blob_data_position = 0;
		return true;
	}

	public static function stream_seek($seek_offset,$seek_whence){
		$blob_data_length = strlen(static::$blob_data_stream);
		switch ($seek_whence) {
			case SEEK_SET:
				$new_blob_data_position = $seek_offset;
				break;
			case SEEK_CUR:
				$new_blob_data_position = static::$blob_data_position+$seek_offset;
				break;
			case SEEK_END:
				$new_blob_data_position = $blob_data_length+$seek_offset;
				break;
			default:
				return false;
		}
		if (($new_blob_data_position >= 0) AND ($new_blob_data_position <= $blob_data_length)){
			static::$blob_data_position = $new_blob_data_position;
			return true;
		}else{
			return false;
		}
	}

	public static function stream_tell(){
		return static::$blob_data_position;
	}

	public static function stream_read($read_buffer_size){
		$read_data = substr(static::$blob_data_stream,static::$blob_data_position,$read_buffer_size);
		static::$blob_data_position += strlen($read_data);
		return $read_data;
	}

	public static function stream_write($write_data){
		$write_data_length=strlen($write_data);
		static::$blob_data_stream = substr(static::$blob_data_stream,0,static::$blob_data_position).
		$write_data.substr(static::$blob_data_stream,static::$blob_data_position+=$write_data_length);
		return $write_data_length;
	}

	public static function stream_eof(){
		return static::$blob_data_position >= strlen(static::$blob_data_stream);
	}

}