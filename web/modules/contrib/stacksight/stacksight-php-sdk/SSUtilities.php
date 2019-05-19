<?php 

class SSUtilities {

	static function getCurlInfoFields(){
		return array(
			"url",
			"http_code",
			"request_size",
			"total_time",
			"connect_time",
			"pretransfer_time",
			"upload_content_length",
			"starttransfer_time",
			"primary_ip",
			"primary_port",
			"local_ip",
			"local_port",
		);
	}

	static function timeJSFormat() {
		$mct = explode(" ", microtime());
		return date("Y-m-d\TH:i:s",$mct[1]).substr((string)$mct[0],1,4).'Z';
	}

	static function error_log($message, $level = 'info', $refresh = false, $to_file = false) {
		if (!$message || (!defined('STACKSIGHT_DEBUG') || (defined('STACKSIGHT_DEBUG') && STACKSIGHT_DEBUG !== true))) return;
	    if (is_array($message) || is_object($message)) $message = print_r($message, true);
		$date = new Datetime();
		$date_format = $date->format('d.m.Y H:i:s');
		if($to_file === true){
			$log_file = dirname(__FILE__).'/../'.$level.'.log';
			// delete logfile if filesize more than $logfile_limit

			$logfile_limit = 1024000; //(100 MB)
			if ((file_exists($log_file) && filesize($log_file) / 1024 > $logfile_limit) || (file_exists($log_file) && $refresh === true))
				unlink($log_file);

			// $date = new Datetime(null, new DateTimeZone('Europe/Minsk'));
			error_log($date_format .' '. $message."\n", 3, $log_file);
		} else{
			error_log($date_format .' '. $message."\n");
		}
	}

	static function t($str, $params = array()) {
		return str_replace(array_keys($params), $params, $str);
	}

	static function currentPageURL() {
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	static function getCurlDescription($type){
		switch($type) {
			case "url":
				return 'Last effective URL';
				break;
			case "content_type":
				return 'Content-Type: of the requested document, NULL indicates server did not send valid Content-Type: header';
				break;
			case "http_code":
				return 'Last received HTTP code';
				break;
			case "header_size":
				return 'Total size of all headers received';
				break;
			case "request_size":
				return 'Total size of issued requests';
				break;
			case "filetime":
				return 'Remote time of the retrieved document, if -1 is returned the time of the document is unknown';
				break;
			case "ssl_verify_result":
				return 'Result of SSL certification verification (requested by setting CURLOPT_SSL_VERIFYPEER)';
				break;
			case "redirect_count":
				return 'Number of redirects (with the CURLOPT_FOLLOWLOCATION option enabled)';
				break;
			case "total_time":
				return 'Total transaction time in seconds for last transfer';
				break;
			case "namelookup_time":
				return 'Time in seconds until name resolving was complete';
				break;
			case "connect_time":
				return 'Time in seconds it took to establish the connection';
				break;
			case "pretransfer_time":
				return 'Time in seconds from start until just before file transfer begins';
				break;
			case "size_upload":
				return 'Total number of bytes uploaded';
				break;
			case "size_download":
				return 'Total number of bytes downloaded';
				break;
			case "speed_download":
				return 'Average download speed';
				break;
			case "speed_upload":
				return 'Average upload speed';
				break;
			case "download_content_length":
				return 'Content-length of download, read from Content-Length: field';
				break;
			case "upload_content_length":
				return 'Specified size of upload';
				break;
			case "starttransfer_time":
				return 'Time in seconds until the first byte is about to be transferred';
				break;
			case "redirect_time":
				return 'Time in seconds of all redirection steps before final transaction was started, with the CURLOPT_FOLLOWLOCATION option enabled';
				break;
			case "certinfo":
				return 'Total info of SSL certification';
				break;
			case "primary_ip":
				return 'IP address of the most recent connection';
				break;
			case "primary_port":
				return 'Destination port of the most recent connection';
				break;
			case "local_ip":
				return 'Local (source) IP address of the most recent connection';
				break;
			case "local_port":
				return 'Local (source) port of the most recent connection';
				break;
			case "redirect_url":
				return 'Redirect URL';
				break;
			case "request_header" :
				return 'The request string sent. For this to work, add the CURLINFO_HEADER_OUT option to the handle by calling curl_setopt()';
				break;
		}
	}
}