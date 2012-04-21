<?php defined('SYSPATH') or die('No direct script access.');
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
	 * Clickatell 2 way callback handler
	 * @param string $key (Unique key that prevents unauthorized access)
	 * @return void
	 */

	function activate($msisdn, $code) {
		
		// Define error codes for this view.
		define("ER_CODE_VERIFIED", 0);
		define("ER_CODE_NOT_FOUND", 1);
		define("ER_CODE_ALREADY_VERIFIED", 3);
		
		$missing_info = FALSE;
		$filter = " ";
		$errno = ER_CODE_ALREADY_VERIFIED;
		
		if (isset($msisdn) AND ! empty($msisdn) AND isset($code) AND ! empty($code)){
			$filter = "alert.alert_type=1 AND alert_code='" . strtoupper($code) . "' AND alert_recipient='" . $msisdn . "' ";
			$missing_info = TRUE;
		}
		
		//echo $msisdn;
		//echo $code;

		

		if (!$missing_info) {
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
			}
		} else {
			$errno = ER_CODE_NOT_FOUND;
		}
		
		die(''.$errno);

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

}
