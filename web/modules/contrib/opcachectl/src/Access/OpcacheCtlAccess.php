<?php

namespace Drupal\opcachectl\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks access for displaying configuration translation page.
 */
class OpcacheCtlAccess implements AccessInterface {



	/**
	 * List of IP addresses / network addresses allowed to access opcachectl sites.
	 *
	 * settings.php:
	 * $config['opcachectl']['request_addr'] = ['127.0.0.1'];
	 *
	 * @var array
	 */
	protected $authorizedAddresses = [];

	/**
	 * Generate token via
	 * #> cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1
	 *
	 * settings.php:
	 * $config['opcachectl']['request_token'] = 'somerandomvalue';
	 *
	 * @var string
	 */
	protected $requestToken;



	/**
	 * Constructs a new OpcacheCtlController object.
	 *
	 */
	public function __construct() {
		$opcachectl_config = \Drupal::service('config.factory')->get('opcachectl');
		$this->requestToken = trim($opcachectl_config->get('request_token'));
		$this->authorizedAddresses = $opcachectl_config->get('request_addr');
	}


	/**
	 * A custom access check.
	 *
	 * @param Request $request
	 *
	 * @return \Drupal\Core\Access\AccessResult
	 */
	public function access(Request $request) {

		$ip = $request->getClientIp();
		if($ip == $_SERVER['SERVER_ADDR']) {
			// Always allow access to "same machine".
			return AccessResult::allowed();
		}

		if(!empty($this->authorizedAddresses)) {
			// Allow access if client IP is whitelisted.
			if( is_array($this->authorizedAddresses)) {
				if(in_array($ip, $this->authorizedAddresses)) {
					return AccessResult::allowed();
				}
			}
			else {
				if($ip == $this->authorizedAddresses) {
					return AccessResult::allowed();
				}
			}
		}
		if (!empty($this->requestToken) && $request->query->has('token')) {
			// Allow access if request contains correct token.
			$token = trim($request->query->get('token'));
			if($token == $this->requestToken) {
				return AccessResult::allowed();
			}
		}

		// Access denied by default.
		return AccessResult::forbidden();
	}

}