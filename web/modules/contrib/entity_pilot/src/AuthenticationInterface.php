<?php

namespace Drupal\entity_pilot;

use Drupal\entity_pilot\Data\FlightManifestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines a class for handling authentication of requests to EntityPilot.
 */
interface AuthenticationInterface {

  /**
   * Signs an outgoing request using the account's black box key.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   Outgoing request.
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $manifest
   *   Manifest to sign request with.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   Signed request.
   */
  public function sign(RequestInterface $request, FlightManifestInterface $manifest);

  /**
   * Verify the incoming response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   Response to be verified.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Account to sign.
   * @param string $nonce
   *   Nonce to verify with.
   *
   * @return bool
   *   TRUE if the response is valid.
   */
  public function verify(ResponseInterface $response, AccountInterface $account, $nonce);

}
