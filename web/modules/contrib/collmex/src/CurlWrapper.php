<?php

namespace Drupal\collmex;

use MarcusJaschen\Collmex\Client\ClientInterface;
use MarcusJaschen\Collmex\Client\Curl;

class CurlWrapper implements ClientInterface {

  /** @var ClientInterface|null */
  protected $client = NULL;

  /**
   * DebugClient constructor.
   *
   * @param string $user
   * @param string $password
   * @param string $customer
   * @param bool $dryrun
   */
  public function __construct($user, $password, $customer, $dryrun) {
    if (!$dryrun) {
      $this->client = new Curl($user, $password, $customer);
    }
  }

  public function request($body) {
    if ($this->client) {
      $response = $this->client->request($body);
    }
    else {
      $response = '';
    }
    return $response;
  }

}
