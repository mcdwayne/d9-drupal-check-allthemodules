<?php

namespace Drupal\drupal_coverage_core\Client;

use Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a SDK for interacting with TravisCI.
 */
class TravisClient {

  /**
   * The base URI of TravisCI API.
   *
   * @var string
   */
  protected $uri = "https://api.travis-ci.org/";

  /**
   * The repository slug used for API version v3.
   *
   * @var string
   */
  protected $slugv3 = "legovaer%2Fdc-travis";

  /**
   * The repository slug used for API version < 3.
   *
   * @var string
   */
  protected $slug = "legovaer/dc-travis";

  /**
   * Default headers used for interacting with TravisCI API.
   *
   * @var array
   */
  protected $headers = [
    'Accept' => 'application/vnd.travis-ci.2+json',
    'Authorization' => 'token Akp85WwlSWzin7OhfUaKTw',
  ];

  /**
   * The amount of re-tries the HttpClient should perform in case of failures.
   */
  const MAX_ATTEMPTS = 10;

  /**
   * Start a build.
   *
   * @param array $body
   *   The body that contains all the information about the build.
   *
   * @return mixed|\Psr\Http\Message\ResponseInterface
   *   The response object.
   *
   * @throws \Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException
   *   Throws error when unable to build.
   */
  public function build(array $body) {
    $url = $this->uri . 'repo/' . $this->slugv3 . '/requests';
    $headers = $this->headers;
    $headers['Travis-API-Version'] = '3';

    try {
      return \Drupal::httpClient()->request('POST', $url,
        [
          'json' => $body,
          'headers' => $headers,
        ]);
    }
    catch (RequestException $e) {
      watchdog_exception('drupal_coverage_core', $e);
      throw new UnableToDetermineBuildStatusException();
    }
  }

  /**
   * Get all builds in history.
   *
   * @todo We have to limit this in the future. In case of 100^n builds, this
   * method will become very slow.
   *
   * @return array
   *   Containing all the the builds in form of \stdClass.
   *
   * @throws \Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException
   *   Throws error when unable to get the build.
   */
  public function getBuilds() {
    $url = $this->uri . 'repos/' . $this->slug . '/builds?limit=1';

    try {
      return json_decode(\Drupal::httpClient()
        ->request('GET', $url)
        ->getBody()
        ->getContents());
    }
    catch (RequestException $e) {
      watchdog_exception('drupal_coverage_core', $e);
      throw new UnableToDetermineBuildStatusException();
    }
  }

  /**
   * Get a specific build.
   *
   * @param int $build_id
   *   The ID of the build.
   *
   * @return \stdClass
   *   The data of the specified build.
   *   Contains following fields:
   *     - id
   *     - repository_id
   *     - number
   *     - state
   *     - result
   *     - started_at
   *     - finished_at
   *     - duration
   *     - commit
   *     - branch
   *     - message
   *     - event_type
   *
   * @throws \Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException
   *   Throws error when unable to build.
   */
  public function getBuild($build_id) {
    $url = $this->uri . 'repos/' . $this->slug . "/builds/$build_id";

    try {
      return json_decode(\Drupal::httpClient()
        ->request('GET', $url)
        ->getBody()
        ->getContents());
    }
    catch (RequestException $e) {
      watchdog_exception('drupal_coverage_core', $e);
      throw new UnableToDetermineBuildStatusException();
    }
  }

  /**
   * Get the last build.
   *
   * @return \stdClass
   *   The object containing the data.
   *
   * @see TravisClient::getBuild()
   */
  public function getLastBuild() {
    $builds = $this->getBuilds();
    return $builds[0];
  }

}
