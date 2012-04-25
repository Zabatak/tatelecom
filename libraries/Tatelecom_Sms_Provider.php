<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Helper library for the Tatelecom API
 *
 * @package     TA-Telecome SMS 
 * @category    Plugins
 * @author      Ushahidi Team
 * @copyright   (c) 2008-2011 Ushahidi Team
 * @license     http://www.gnu.org/copyleft/lesser.html GNU Less Public General License (LGPL)
 */
class Tatelecom_Sms_Provider implements Sms_Provider_Core {
	
	/**
	 * Sends a text message (SMS) using the TA-Telecom API
	 *
	 * @param string $to
	 * @param string $from
	 * @param string $to
	 */
	public function send($to = NULL, $from = NULL, $message = NULL)
	{
		
		/*
		 * MSISDN : the phone number
		 * Language  : 0 for English , 1 for Arabic
		 * Message : the message that will be sent
		 * Sender : the originator or SC 
		 */
		 
		$ip= Kohana::config('tatelecom.ip');
		$sender = Kohana::config('tatelecom.sender');	
		$language=0;
		$url = Kohana::config('tatelecom.url');
		
		$final_url = sprintf ($url, $ip, $to, $language,rawurlencode($message), $sender);
		

		return $this->call_url($final_url);
	}
	
	
	private function call_url($url){
		if (!function_exists('curl_exec'))
			{
				throw new Kohana_Exception('settings.updateCities.cURL_not_installed');
				return false;
			}

			Kohana::log('debug', 'CURL '.$url );
			// Use Curl
			$ch = curl_init();
			$timeout = 20;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
			$xmlstr = curl_exec($ch);
			$err = curl_errno( $ch );
			curl_close($ch);
			return $xmlstr;
	}
	
	
	
}
