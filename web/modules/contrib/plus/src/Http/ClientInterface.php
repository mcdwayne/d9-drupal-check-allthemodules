<?php

namespace Drupal\plus\Http;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;

/**
 * Interface ClientInterface.
 */
interface ClientInterface extends GuzzleClientInterface {

  const TTL_MINUTE = 60;
  const TTL_HOUR = 3600;
  const TTL_DAY = 86400;
  const TTL_WEEK = 604800;
  const TTL_MONTH = 2419200;
  const TTL_QUARTER = 7257600;
  const TTL_YEAR = 31449600;

  /**
   * Adds a user agent to the HTTP client.
   *
   * @param string|\Drupal\Core\Extension\Extension $user_agent
   *   A user agent label. This can be a string representation of an extension,
   *   e.g. "type:name", where "type" is the extension type and "name" is the
   *   machine name of the extension, e.g.: "module:block" or "theme:bartik".
   *   It may also be an direct Extension object.
   * @param string $version
   *   Optional. The version of the user agent. If not provided and $user_agent
   *   is an extension, this will automatically be determined as the version
   *   of the extension that is currently installed.
   * @param string $url
   *   Optional. The URL of the extension. If not provided and $user_agent is
   *   an extension, this will automatically default to prefixing the
   *   machine name of the extension with: "https://www.drupal.org/project/".
   *   In the event that the extension is not an actual project on drupal.org,
   *   you may wish to provide an alternate URL or explicitly set this to FALSE
   *   if you wish to not include a URL for the user agent.
   *
   * @return static
   */
  public function addUserAgent($user_agent, $version = NULL, $url = NULL);

  /**
   * Caches the response from a request.
   *
   * @param string $method
   *   HTTP method.
   * @param string|\Psr\Http\Message\UriInterface $uri
   *   URI object or string.
   * @param array $options
   *   Request options to apply.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   */
  public function cacheableRequest($method, $uri = '', array $options = []);

  /**
   * Retrieves the UserAgent object so it can be decorated.
   *
   * @return \Drupal\plus\Http\UserAgent
   *   The UserAgent object.
   */
  public function getUserAgent();

}
