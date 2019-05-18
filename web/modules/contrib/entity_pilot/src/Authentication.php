<?php

namespace Drupal\entity_pilot;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Random;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines a class for handling authentication of requests to EntityPilot.
 */
class Authentication implements AuthenticationInterface {

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  protected $randomGenerator;

  /**
   * {@inheritdoc}
   */
  public function sign(RequestInterface $request, FlightManifestInterface $manifest) {
    /* @var \Psr\Http\Message\RequestInterface $request */
    $request = $request->withHeader('X-EP-Account-ID', $manifest->getCarrierId());
    $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('c');
    if (in_array($request->getMethod(), ['PUT', 'POST', 'PATCH'])) {
      $body = (string) $request->getBody();
    }
    else {
      $body = $this->getRandomGenerator()->string(32, TRUE);
      $request = $request->withHeader('X-EP-Digest', $body);
    }
    $request = $request->withHeader('X-EP-Timestamp', $timestamp);
    $nonce = Crypt::randomBytesBase64(24);
    $request = $request->withHeader('X-EP-Nonce', $nonce);
    return $request->withHeader('X-EP-Hash', hash_hmac('sha512', $body . $timestamp . $nonce, $manifest->getBlackBoxKey()));
  }

  /**
   * {@inheritdoc}
   */
  public function verify(ResponseInterface $response, AccountInterface $account, $nonce) {
    $body = (string) $response->getBody();
    $hash = $response->getHeader('X-EP-Hash');
    return hash_hmac('sha512', $body . $nonce, $account->getBlackBoxKey()) === reset($hash);
  }

  /**
   * Gets the random generator for the utility methods.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random generator
   */
  protected function getRandomGenerator() {
    if (!is_object($this->randomGenerator)) {
      $this->randomGenerator = new Random();
    }
    return $this->randomGenerator;
  }

}
