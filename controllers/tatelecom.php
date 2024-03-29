<?php

defined('SYSPATH') or die('No direct script access.');

/**
 * Clickatell HTTP Post Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com>
 * @package	   Ushahidi - http://source.ushahididev.com
 * @module	   Clickatell Controller
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
class Tatelecom_Controller extends Controller {

	private $request = array();

	public function __construct() {
		$this -> request = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
	}

	/**
	 * tatelecom activation method
	 * to test: http://localhost/tatelecom/activate/0100123456/123456
	 * @param msisdn 11 number
	 * @param code 8 numbers
	 * @return
	 */
	function activate($msisdn, $code) {
		Kohana::log('debug', "welcome to Ta-Telecome, Activation , msisdn [ " . $msisdn . " ] Code is [ " . $code . " ]");
		//put all inputs in array to validate them
		$inputs = array();

		// Define error codes for this view.
		define("ER_CODE_VERIFIED", 0);
		define("ER_CODE_NOT_FOUND", 1);
		define("ER_CODE_NOT_VALID", 2);
		define("ER_CODE_ALREADY_VERIFIED", 3);

		$filter = " ";
		$errno = ER_CODE_ALREADY_VERIFIED;


		/*
		 * MSISDN will look like 20xxxxxxxxx (12 numbers)
		 * code consist of 4 digits
		 * remove all spaces before, after and inside the code
		 * remove any characters inside the code
		 * return 0 for success and 1 for anything
		 */

		//remove non nummeric from code var , if we have 1a2b3c4 result would be 1234
		$code = preg_replace("/[^0-9]/", "", $code);

		$inputs[] = $msisdn;
		$inputs[] = $code;

		//print final values in array
		//print_r($inputs);
		$validate = new Validation($inputs);
		$validate -> pre_filter('trim') -> add_rules('msisdn', 'numeric', 'length[12]') -> add_rules('code', 'numeric', 'length[4]');

		// Test to see if things passed the rule checks
		if ($validate -> validate()) {
			// Yes! everything is valid , we need to send 0
			$filter = "alert.alert_type=1 AND alert_code='" . strtoupper($code) . "' AND alert_recipient='" . $msisdn . "' ";
			$alert_check = ORM::factory('alert') -> where($filter) -> find();

			// IF there was no result
			if (!$alert_check -> loaded) {
				$errno = ER_CODE_NOT_FOUND;
			} elseif ($alert_check -> alert_confirmed) {
				$errno = ER_CODE_ALREADY_VERIFIED;
			} else {
				// SET the alert as confirmed, and save it
				$alert_check -> set('alert_confirmed', 1) -> save();
				$errno = ER_CODE_VERIFIED;
				
				$sms_result = sms::send($msisdn, 'ZabatakCom', 'SMS Service Activated Successfully. Thank you for using Zabatak.com');
				Kohana::log('debug', 'SMS return message '. $sms_result);
				
			}

		}
		// No! We have validation errors, we need to send 1 for error
		else {
			$errno = ER_CODE_NOT_VALID;
			// populate the error fields, if any
			Kohana::log('debug', 'Submission have some error , inputs was msisdn[' . $msisdn . '], code [' . $code . ']');
		}

		//log everything
		Kohana::log('debug', 'MSISDN: ' . $msisdn . ' code: ' . $code . ' => ' . $errno);
		//print $errno and die
		die('' . ($errno === 0 ? 0 : 1));
	}

	function index($key = NULL) {
		if (isset($this -> request['from'])) {
			$message_from = $this -> request['from'];
			// Remove non-numeric characters from string
			$message_from = preg_replace("#[^0-9]#", "", $message_from);
		}

		if (isset($this -> request['to'])) {
			$message_to = $this -> request['to'];
			// Remove non-numeric characters from string
			$message_to = preg_replace("#[^0-9]#", "", $message_to);
		}

		if (isset($this -> request['text'])) {
			$message_description = $this -> request['text'];
		}

		if (!empty($message_from) AND !empty($message_description)) {
			// Is this a valid Clickatell Key?
			$keycheck = ORM::factory('clickatell') -> where('clickatell_key', $key) -> find(1);

			if ($keycheck -> loaded == TRUE) {
				sms::add($message_from, $message_description, $message_to);
			}
		}
	}

	public function geocode() {
		print_r(Tatelecom_Controller::reverse_geocode(30.039043, 31.408539));
		//30.040311,31.398298
	}

	/**
	 * Reverse Geocode a point
	 *
	 * @author
	 * @param   double  $latitude
	 * @param   double  $longitude
	 * @return  string  closest approximation of the point as a display name
	 */
	public static function reverse_geocode($latitude, $longitude) {
		if ($latitude AND $longitude) {
			$url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng=' . $latitude . ',' . $longitude . '&sensor=false&language=ar';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$json = curl_exec($ch);
			curl_close($ch);

			$location = json_decode($json, false);
			print_r($location -> results);

			return $location -> results[0] -> formatted_address;
		} else {
			return false;
		}
	}

	public function test() {
		echo sms::send('201001374126', 'zabatak', 'test') . '';
		//sms::send($alertee->alert_recipient, $sms_from, $sms_message)
	}

	/**
	 * Check for CURL PHP module
	 * @access private
	 */
	function _chk_curl() {
		if (!extension_loaded('curl')) {
			return "This SMS API class can not work without CURL PHP module! Try using fopen sending method.";
		}
	}

	/**
	 * CURL sending method
	 * @access private
	 */
	function _curl($command) {
		$this -> _chk_curl();
		$ch = curl_init($command);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		if ($this -> curl_use_proxy) {
			curl_setopt($ch, CURLOPT_PROXY, $this -> curl_proxy);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this -> curl_proxyuserpwd);
		}
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

}
