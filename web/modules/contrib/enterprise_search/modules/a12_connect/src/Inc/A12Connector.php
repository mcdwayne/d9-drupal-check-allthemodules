<?php
/**
 * @file
 * Provides functionality to connect with A12 webservices. Currently this is 
 * only used with the Enterprise Search module but can be used in the future to
 * connect to other services.
 */

namespace Drupal\a12_connect\Inc;

use Drupal\a12_connect\Inc\A12ConnectorException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class A12Connector {
  const WEBSERVICES_URL = 'http://192.168.81.2/findcredentials/validate';
  const VALIDATE_METHOD = 'a12_webservices.validate';

  protected $accessKey;
  protected $secretKey;
  protected $url;

  /**
   * Constructor.
   *
   * Sets up the keys, site and timestamp that is used to generate the headers
   * that are going to be passed to the A12 servers. 
   *
   * @param string $access_key
   *   The access key for the current users subscription.
   * @param string $secret_key
   *   The secret key for the current users subscription.
   * @param string $url
   *   The URL of the site that the user is connecting from. This must match the
   *   URL that we are making the request from.
   */
  public function __construct($access_key, $secret_key) {
    $this->accessKey = $access_key;
    $this->secretKey = $secret_key;
  }

  /**
   * Perform the authentication request against the A12 XMLRPC server.
   * 
   * @throws A12ConnectorException
   * @return bool
   *   TRUE if the user has successfully authenticated against the server.
   */
  public function authenticate() {
    $url = self::WEBSERVICES_URL;
    $client = new Client();
    $body = array(
        'username' => $this->accessKey,
        'password' => $this->secretKey
    );
    $request = new Request('POST', $url, array(), json_encode($body));
    $response = $client->send($request);
    if (!$response || $response->getBody()->getContents() !== 'FND-200') {
      throw new A12ConnectorException($response->getBody()->getContents());
    }

    return TRUE;
  }
}
