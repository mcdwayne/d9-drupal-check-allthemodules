<?php

/**
 * @file
 * Definition of ServiceHttps.
 */

namespace WoW\Core\Service;

use WoW\Core\ServiceInterface;

/**
 * Service performs GET operations against battle.net API.
 *
 * This service is meant to be used for sending authenticated requests.
 */
class ServiceHttps extends ServiceHttp implements ServiceInterface {

  /**
   * The private key.
   *
   * @var string
   */
  private $key;

  /**
   * The public key.
   *
   * @var string
   */
  private $publicKey;

  /**
   * Constructs a Service HTTPS object.
   *
   * @param string $region
   *   The service region.
   * @param array $locales
   *   The service locales.
   * @param string $public_key
   *   The public key.
   * @param string $key
   *   The private key.
   */
  public function __construct($region, array $locales, $public_key, $key) {
    $this->key = $key;
    $this->publicKey = $public_key;
    parent::__construct($region, $locales);
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\ServiceInterface::request()
   */
  public function request($path, array $query = array(), array $headers = array()) {
    // Creates the data string to sign request with.
    $data = "GET\n{$headers['Date']}\n/api/wow/$path\n";

    // Sign the data string using the private key.
    $signature = base64_encode(hash_hmac('sha1', $data, $this->key, TRUE));

    // Sign the request with the public key.
    $headers['Authorization'] = "BNET $this->publicKey:$signature";

    $options = array('absolute' => TRUE, 'external' => TRUE, 'query' => $query);
    $url = url("https://{$this->getHost()}/api/wow/$path", $options);

    return $this->__request($url, array('headers' => $headers));
  }

}
