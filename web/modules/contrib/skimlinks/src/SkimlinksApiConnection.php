<?php

namespace Drupal\skimlinks;

use Drupal\Core\Url;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\RequestException;

/**
 * Class SkimlinksApiConnection
 *
 * @package Drupal\skimlinks
 */
class SkimlinksApiConnection {

	/**
   * @var string API querying method
   */
  private $method  = 'GET';
  private $timeout = 30;

  public function __construct() {
    $this->config = \Drupal::config('skimlinks.settings');
  }

  /**
   * Get configuration or state setting for this Skimlinks integration module.
   * This is a stub function for if we need more privacy for certain settings,
   * e.g. private keys.
   *
   * @param string $name this module's config or state.
   *
   * @return mixed
   */
  protected function getConfig($name = FALSE) {
    return $name ? $this->config->get($name) : $this->config->get();
  }

  public function getDomain($domain, $options = []) {
    if (!empty($options['timeout'])) {
      $this->timeout = $options['timeout'];
    }
  	$response = $this->request([
  		'search' => $domain,
  	]);
  	if ($response) {
  		return json_decode($response->getBody());
  	}
  }

  /**
   * Call the Skimlinks API endpoint.
   *
   * @param string $endpoint
   * @param array  $options
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function request($options) {
  	$headers = $this->generateHeaders();
    $url = $this->requestUrl($options)
      ->toString();

    $client = new GuzzleClient();
    $request = new GuzzleRequest($this->method, $url, $headers);
    try {
	    $response = $client->send($request, ['timeout' => $this->timeout]);
      return $response;
	  }
	  catch(RequestException $e) {
	  	/**
	  	 * @todo watchdog instead?
	  	 */
			drupal_set_message(t("The Skimlinks API returned a :code error:\n:message", [
				':code' => $e->getCode(),
				':message' => $e->getMessage(),
			]), 'warning');
      return FALSE;
	  }
  }

  /**
   * Build an array of headers to pass to the Iguana API such as the
   * signature and account. Another stub.
   *
   * @param string $request_uri to the API endpoint
   *
   * @return array
   */
  protected function generateHeaders() {
    $headers = [];
    return $headers;
  }

  /**
   * Build a Url object of the URL data to query the Skimlinks API.
   *
   * @param array  $params additional query parameters.
   *
   * @return \Drupal\Core\Url
   */
  protected function requestUrl($params = []) {
    $url_query = array_merge(
    	[
	      'apikey' => $this->getConfig('merchant_api_key'),
	      'account_type' => $this->getConfig('merchant_api_account_type'),
	      'account_id' => $this->getConfig('merchant_api_account_id'),
	    ],
	    $params
    );

    return Url::fromUri($this->getConfig('merchant_api_endpoint'), ['query' => $url_query]);
  }

}
