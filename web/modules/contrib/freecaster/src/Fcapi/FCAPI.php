<?php
/**
 * Freecaster API class
 *
 * $Id: FCAPI.php 7377 2015-06-22 14:38:12Z yannick $
 *
 * @package   Freecaster Engine
 * @author    Yannick Delwiche
 * @copyright (c) 2012-2014 Kjerag S.A.
 */
namespace Drupal\freecaster\Fcapi;

//class FCException extends Exception {};

class FCAPI
{
	private $entrypoint = 'https://freecaster.tv/api/';
	private $user_id;
	private $api_key;

	private $format;
	private $debug;

	public $num_records = null;
	public $headers_out = null;
	public $body_out = null;
	public $headers_in = null;
	public $body_in = null;

//	__ Public ___________________________________________________________________

	public function __construct($user_id, $api_key, $entrypoint = null, $debug = false, $format = 'json')
	{
		$this->user_id = $user_id;
		$this->api_key = $api_key;

		$this->format = ($format == 'xml') ? 'text/xml' : 'application/json';
		$this->debug = $debug;

		if (!empty($entrypoint)) $this->entrypoint = $entrypoint;
	}

	public function __call($method, $data = array())
	{
		$url = $method;

		if (count($data) >= 1)
		{
			while ((count($data) > 0) && (!is_array($data[0])))
			{
				$url .= '/'.array_shift($data);
			}
			if (is_array($data[0]))
			{
				$data = $data[0];
			}
			if (isset($data[0]))
			{
				$data = array('data' => $data);
			}
		}

		// Add the user ID parameter
		$data['fc_uid'] = $this->user_id;
		// Add the timestamp
		$data['ts'] = time();

		// Compute the normalized call
		ksort($data);
		$normalized_data = array();
		foreach ($data as $key => &$val)
		{
			// Empty values should be explicitly set to not be filtered out by the cURL call
			if ($val===null) $val = 'null';
			// Do not include file uploads in the normalized call verification
			if ((is_string($val)) && ($val[0]=='@'))
			{
				$file = substr($val, 1);
				if (empty($file))
				{
					unset($data[$key]);
					continue;
				}
				$val = curl_file_create($file);
				continue;
			}
			if ((is_array($val)) || (is_object($val))) $val = json_encode($val);
			$normalized_data[$key] = $val;
		}
		$normalized_call = $url.'?'.http_build_query($normalized_data);

		// Set the call signature
		$data['fc_sig'] = sha1($this->api_key.$normalized_call);

		// If some values are array, we need the URL-encoded string
		foreach ($data as &$val)
		{
			if (is_array($val))
			{
				$data = http_build_query($data);
				break;
			}
		}

		$this->num_records = null;

		// Call the API server
		$curl = curl_init($this->entrypoint.$url);
		curl_setopt_array($curl, array(
			CURLOPT_POST => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Accept: '.$this->format),
			CURLOPT_POSTFIELDS => $data,
		));
		if ($http_proxy = $this->get_proxy())
		{
			curl_setopt($curl, CURLOPT_PROXY, $http_proxy);
		}
		if ($this->debug)
		{
			curl_setopt_array($curl, array(
				CURLOPT_HEADER => true,
				CURLINFO_HEADER_OUT => true,
			));
			if (empty($_COOKIE['XDEBUG_SESSION']))
			{
				curl_setopt($curl, CURLOPT_COOKIE, 'XDEBUG_SESSION=DEBUG');
			}
		}
		else
		{
			curl_setopt($curl, CURLOPT_HEADER, false);
		}
		$result = curl_exec($curl);
		$errno = curl_errno($curl);
		$error = curl_error($curl);
		if ($this->debug)
		{
			$this->headers_out = curl_getinfo($curl, CURLINFO_HEADER_OUT);
			$this->body_out = (is_array($data)) ? http_build_query($data) : $data;
		}
		curl_close($curl);

		if (!empty($error))
		{
//			throw new FCException('Error '.$errno.': '.$error);
		}

		if ($this->debug)
		{
			list($this->headers_in, $this->body_in) = explode("\r\n\r\n", $result);
			$result = $this->body_in;
		}

		// Decode the result
		$result = @json_decode($result);
		// Check for errors
		if (empty($result))
		{
//			throw new FCException('Unknown API Error');
		}
		if (!empty($result->error))
		{
//			throw new FCException('API Error: '.$result->error);
		}
		// Return true if we received a simple "OK"
		if ((!empty($result->info)) && ($result->info=='OK')) return true;

		// If we requested objects, return them instead of the server reply
		list($action, $request, ) = preg_split('#[_/]#', $method, 3);
		if ((($action == 'get') || ($action == 'create')) && (isset($result->$request)))
		{
			if (isset($result->num_records)) $this->num_records = $result->num_records;
			return $result->$request;
		}

		return $result;
	}

//	__ Private __________________________________________________________________

	/**
	 * Get the web server proxy settings
	 *
	 * @return string
	 *   Proxy host:port
	 */
	private function get_proxy()
	{
		if (!empty($_ENV['http_proxy']))
		{
			return $_ENV['http_proxy'];
		}
		else if (!empty($_SERVER['http_proxy']))
		{
			return $_SERVER['http_proxy'];
		}
		return '';
	}

}
