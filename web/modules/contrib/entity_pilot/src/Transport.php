<?php

namespace Drupal\entity_pilot;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\entity_pilot\Data\FlightManifest;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\Exception\EncryptionException;
use Drupal\entity_pilot\Exception\TransportException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

/**
 * Defines a service to transport flights to and from EntityPilot.
 */
class Transport implements TransportInterface {

  /**
   * Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Authentication service.
   *
   * @var \Drupal\entity_pilot\AuthenticationInterface
   */
  protected $authentication;

  /**
   * JSON Encoder.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $jsonEncoder;

  /**
   * Constructs a new \Drupal\entity_pilot\Transport object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The json serializer service.
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client service.
   * @param \Drupal\entity_pilot\AuthenticationInterface $authentication
   *   The entity pilot authentication service.
   */
  public function __construct(SerializationInterface $serializer, ClientInterface $client, AuthenticationInterface $authentication) {
    $this->jsonEncoder = $serializer;
    $this->httpClient = $client;
    $this->authentication = $authentication;
  }

  /**
   * Post a new flight to EntityPilot.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $manifest
   *   Flight to send.
   * @param string $secret
   *   Secret.
   *
   * @return int
   *   Remote flight ID.
   */
  protected function postFlight(FlightManifestInterface $manifest, $secret) {
    $url = TransportInterface::ENTITY_PILOT_URI . TransportInterface::FLIGHT_ENDPOINT;
    return $this->doSendFlight($manifest, $secret, 'POST', $url, function ($response) {
      if ($response->getStatusCode() == '403') {
        throw new TransportException('Access denied. Please check your account credentials and that the time on your server is accurate.', 403);
      }
      if ($response->getStatusCode() == '413') {
        throw new TransportException('The flight you are sending is too large. Please break it up into smaller flights or consider reducing the size of original images.', 413);
      }
      if ($response->getStatusCode() != '201') {
        throw new TransportException('Encountered an unexpected response when sending the flight - please try again later.');
      }
    });
  }

  /**
   * Send an updated flight to EntityPilot.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $manifest
   *   Flight to send.
   * @param string $secret
   *   Secret.
   *
   * @return int
   *   Remote flight ID.
   */
  protected function putFlight(FlightManifestInterface $manifest, $secret) {
    $url = TransportInterface::ENTITY_PILOT_URI . TransportInterface::FLIGHT_ENDPOINT . '/' . $manifest->getRemoteId();
    return $this->doSendFlight($manifest, $secret, 'PUT', $url, function ($response) {
      if ($response->getStatusCode() != '204') {
        throw new TransportException('Encountered an unexpected response when updating the flight - please try again later.');
      }
    });
  }

  /**
   * {@inheritdoc}
   */
  public function sendFlight(FlightManifestInterface $manifest, $secret) {
    if ($manifest->getRemoteId()) {
      return $this->putFlight($manifest, $secret);
    }
    else {
      return $this->postFlight($manifest, $secret);
    }
  }

  /**
   * Sets required headers on request.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request to set the headers on.
   *
   * @return \Psr\Http\Message\RequestInterface
   *   The request with the added headers.
   */
  protected function setHeaders(RequestInterface $request) {
    return $request->withHeader('Content-Type', 'application/json')
      ->withHeader('Accept', 'application/json')
      ->withHeader('X-EP-Drupal-Version', FlightManifest::DRUPAL_VERSION);
  }

  /**
   * {@inheritdoc}
   */
  public function queryFlights(AccountInterface $account, $search_string = '', $limit = 50, $offset = 0) {
    $url = TransportInterface::FLIGHT_ENDPOINT;
    $options = [
      'query' => [
        'search' => $search_string,
        'offset' => $offset,
        'limit' => $limit,
      ],
    ];
    return $this->performGetRequest($url, $account, FALSE, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlight($remote_id, AccountInterface $account) {
    $url = TransportInterface::FLIGHT_ENDPOINT . '/' . $remote_id;
    $flights = $this->performGetRequest($url, $account, TRUE);
    return reset($flights);
  }

  /**
   * Perform GET request to Entity Pilot for a given account and URL.
   *
   * @param string $path
   *   End-point to GET from.
   * @param \Drupal\entity_pilot\AccountInterface $account
   *   Account to search in.
   * @param bool $single_record
   *   (optional) TRUE if expected return contains only a single record.
   *   Defaults to FALSE.
   * @param array $options
   *   (optional) Additional options such as query etc.
   *
   * @return \Drupal\entity_pilot\Data\FlightManifestInterface[]
   *   Flight manifest with content set on it.
   *
   * @throws \Drupal\entity_pilot\Exception\TransportException
   *   In case where error occured.
   */
  protected function performGetRequest($path, AccountInterface $account, $single_record = FALSE, array $options = []) {
    $manifest = FlightManifest::create()
      ->setCarrierId($account->getCarrierId())
      ->setBlackBoxKey($account->getBlackBoxKey());
    $request = new Request('GET', TransportInterface::ENTITY_PILOT_URI . $path);
    $request = $this->authentication->sign($request, $manifest);
    $nonce = $request->getHeader('X-EP-Nonce');
    $request = $this->setHeaders($request);
    // No timeout.
    $options['timeout'] = 0;
    try {
      $response = $this->httpClient->send($request, $options);
      if (!$this->authentication->verify($response, $account, reset($nonce))) {
        throw new TransportException('The response received from Entity Pilot was not able to be verified, please try again.', TransportException::VERIFICATION_FAILED);
      }
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      if ($response === NULL) {
        throw new TransportException('Error connecting to Entity Pilot backend', TransportException::UNKNOWN_EXCEPTION, $e);
      }
      if ($response->getStatusCode() == 403) {
        throw new TransportException('Authentication failure connecting to the Entity Pilot backend - please check your account details and verify that the time on your server is accurate.', TransportException::AUTHENTICATION_FAILED, $e);
      }
    }
    if ($response->getStatusCode() != '200') {
      throw new TransportException('An unexpected response was returned from the Entity Pilot backend', TransportException::UNKNOWN_EXCEPTION);
    }

    $body = $this->jsonEncoder->decode((string) $response->getBody());
    $secret = $account->getSecret();
    if ($single_record) {
      if (isset($body['drupal_version']) && version_compare($body['drupal_version'], FlightManifest::DRUPAL_VERSION) === -1) {
        $secret = $account->getLegacySecret();
      }
      $body = [$body];
    }
    try {
      return FlightManifest::fromArray($body, $secret);
    }
    catch (EncryptionException $e) {
      watchdog_exception('entity_pilot', $e, sprintf('Could not decrypt entity %s', $e->getUuid()));
      throw new TransportException('Unable to decrypt the response from Entity Pilot backend', TransportException::UNKNOWN_EXCEPTION, $e);
    }
  }

  /**
   * Sends a flight to Entity Pilot backend.
   *
   * @param \Drupal\entity_pilot\Data\FlightManifestInterface $manifest
   *   Flight to send.
   * @param string $secret
   *   Secret.
   * @param string $method
   *   One of POST|PUT.
   * @param string $url
   *   URL to send to.
   * @param callable $callback
   *   Response callback.
   *
   * @return int
   *   Remote ID on success.
   *
   * @throws \Drupal\entity_pilot\Exception\TransportException
   *   When failure occurs.
   */
  protected function doSendFlight(FlightManifestInterface $manifest, $secret, $method, $url, callable $callback) {
    try {
      $body = $this->jsonEncoder->encode($manifest->toArray($secret));
    }
    catch (EncryptionException $e) {
      watchdog_exception('entity_pilot', $e, sprintf('Could not encrypt entity %s', $e->getUuid()));
    }
    $request = new Request($method, $url, [], $body);
    $request = $this->authentication->sign($request, $manifest);
    $request = $this->setHeaders($request);
    $e = NULL;
    try {
      set_time_limit(0);
      $response = $this->httpClient->send($request, [
        // Avoid timeouts.
        'timeout' => 0,
        '_body_as_string' => TRUE,
      ]);
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      if ($response === NULL) {
        throw $e;
      }
    }
    if ($response->getStatusCode() === 429) {
      $retry_after = $response->getHeader('Retry-After');
      $retry_after = reset($retry_after);
      $resets = (new \DateTime())->modify("+ $retry_after seconds")
        ->format('d-m-Y');
      throw new TransportException(sprintf('You are over your monthly quota, which resets on %s. Alternatively visit https://entitypilot.com and choose an alternate plan.', $resets), TransportException::QUOTA_EXCEEDED, $e);
    }
    $callback($response);
    if (!($location = $response->getHeader('Location'))) {
      throw new TransportException('Location header not found');
    }
    $path = parse_url(reset($location), PHP_URL_PATH);
    $basename = basename($path);
    list($remote_id,) = explode('.', $basename);
    $manifest->setRemoteId($remote_id);
    return $remote_id;
  }

}
