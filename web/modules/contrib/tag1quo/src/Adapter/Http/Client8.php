<?php

namespace Drupal\tag1quo\Adapter\Http;

use Drupal\tag1quo\Adapter\Core\Core;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

/**
 * Class Client8.
 *
 * @internal This class is subject to change.
 */
class Client8 extends Client {

  /**
   * Drupal 8's HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(Core $core) {
    parent::__construct($core);
    $this->httpClient = \Drupal::httpClient();
  }

  /**
   * {@inheritdoc}
   */
  protected function doRequest(Request $request) {
    try {
      $response = $this->httpClient->request($request->getMethod(), $request->getUri(), $request->getOptions());
    }
    catch (RequestException $e) {
      $response = $e->getResponse() ?: new GuzzleResponse(500, array(), $e->getMessage());
    }
    catch (GuzzleException $e) {
      $response = new GuzzleResponse(500, array(), $e->getMessage());
    }
    $body = $response->getBody();

    return $this->createResponse($body ? $body->getContents() : '', $response->getStatusCode(), $response->getHeaders());
  }

}
