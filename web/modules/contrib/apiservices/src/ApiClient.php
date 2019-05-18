<?php

/**
 * @file
 * Contains \Drupal\apiservices\ApiClient.
 */

namespace Drupal\apiservices;

use Drupal\apiservices\Exception\ApiServiceException;
use Drupal\apiservices\Exception\EndpointException;
use Drupal\apiservices\Exception\EndpointDeniedException;
use Drupal\apiservices\Exception\EndpointNotFoundException;
use Drupal\apiservices\Exception\EndpointRequestException;
use Drupal\apiservices\Exception\EndpointServerException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * A client for making API requests that also caches responses.
 */
class ApiClient implements ApiClientInterface {

  /**
   * The cache backend to store API responses.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The HTTP client used to make requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an ApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface
   *   The HTTP client to make requests with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to store API responses.
   * @param \Psr\Log\LoggerInterface
   *   A logger instance.
   */
  public function __construct(ClientInterface $client, CacheBackendInterface $cache_backend, LoggerInterface $logger) {
    $this->client = $client;
    $this->cache = $cache_backend;
    $this->logger = $logger;
  }

  /**
   * Caches the response to an API request.
   *
   * When caching a response, directives from the API server will take
   * precedence over settings from the API provider.
   *
   * @param \Drupal\apiservices\ApiProviderInterface $provider
   *   The API provider used to create the HTTP request.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   *
   * @return \Drupal\apiservices\ApiResponseInterface|FALSE
   *   The cached response, or FALSE if it could not be cached.
   */
  protected function cacheResponse(ApiProviderInterface $provider, ResponseInterface $response) {
    $cid = $provider->getRequestUrl();

    $date = FALSE;
    if ($response->hasHeader('Date')) {
      $date = strtotime($response->getHeaderLine('Date'));
    }
    // Ignore attempts to cache responses with no 'Date' header.
    if ($date === FALSE) {
      return FALSE;
    }

    $expire = FALSE;
    $lifetime = CacheControl::getResponseLifetime($response);

    if ($provider instanceof CacheableApiInterface) {
      $cid = $provider->getCacheId();
      $expire = $provider->getCacheLifetime();
      if ($expire != CacheBackendInterface::CACHE_PERMANENT) {
        $expire = $date + $expire;
      }
    }

    if ($lifetime === FALSE && $expire === FALSE) {
      return FALSE;
    }

    // Header directives override API settings.
    $expire = $lifetime === FALSE ? $expire : $date + $lifetime;

    $response = $this->removeConnectionHeaders($response);

    // Since many API responses do not contain expiration and cache validation
    // headers, do not check for those. The responses may still be cached.
    if (CacheControl::isResponseCacheable($response, time(), FALSE)) {
      $result = new GuzzleResponse($response);
      $this->cache->set($cid, $result, $expire);
      return $result;
    }

    if ($response->getStatusCode() == 304) {
      $cache = $this->getCachedResponse($provider);
      if ($cache !== FALSE) {
        $result = $this->updateCachedResponse($cache->data, $response);
        $this->cache->set($cid, $result, $expire);
        return $result;
      }
    }

    return FALSE;
  }

  /**
   * Gets the response for the given request from the persistent cache.
   *
   * @param \Drupal\apiservices\ApiProviderInterface $provider
   *   The API provider used to create the original request.
   * @param bool $allow_invalid
   *   (optional) If TRUE, a cache item may be returned even if it is expired
   *   or has been invalidated. The 'valid' property of the returned object
   *   indicates whether the item is valid or not. Defaults to FALSE.
   *
   * @return object|FALSE
   *   An object containing the cached data (and other properties from the
   *   underlying cache backend) or FALSE if the given item was not in the
   *   cache.
   */
  protected function getCachedResponse(ApiProviderInterface $provider, $allow_invalid = FALSE) {
    $cid = $provider->getRequestUrl();
    if ($provider instanceof CacheableApiInterface) {
      $cid = $provider->getCacheId();
    }
    return $this->cache->get($cid, $allow_invalid);
  }

  /**
   * Processes the response from an API request.
   *
   * @param \Drupal\apiservices\ApiProviderInterface $provider
   *   The API provider used to create the HTTP request.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The HTTP request.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   *
   * @return \Drupal\apiservices\ApiResponseInterface
   *   An API response.
   */
  protected function handleResponse(ApiProviderInterface $provider, RequestInterface $request, ResponseInterface $response) {
    $date = FALSE;
    if ($response->hasHeader('Date')) {
      $date = strtotime($response->getHeaderLine('Date'));
    }

    // RFC 7231: A recipient with a clock that receives a response message
    // without a Date header field MUST record the time it was received and
    // append a corresponding Date header field to the message's header section
    // if it is cached or forwarded downstream.
    if ($date === FALSE) {
      $response = $response->withHeader('Date', date('r'));
    }

    if (CacheControl::isRequestCacheable($request)) {
      if ($result = $this->cacheResponse($provider, $response)) {
        return $result;
      }
    }

    return new GuzzleResponse($response);
  }

  /**
   * Removes hop-by-hop headers from a response.
   *
   * These headers are only meaningful for a single transport-level connection
   * and should not be stored by caches or forwarded by proxies.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response without hop-by-hop headers.
   *
   * @link https://tools.ietf.org/html/rfc7230#section-6.1
   */
  protected function removeConnectionHeaders(ResponseInterface $response) {
    $removable = CacheControl::$HOP_BY_HOP_HEADERS;
    if ($response->hasHeader('Connection')) {
      $removable = array_merge($removable, $response->getHeader('Connection'));
    }
    foreach ($removable as $header) {
      $response = $response->withoutHeader($header);
    }
    return $response;
  }

  /**
   * Updates the headers of the cached response with those of the 304 response.
   *
   * @param \Drupal\apiservices\ApiResponseInterface $cached
   *   A cached API response.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The '304 Not Modified' response.
   *
   * @return \Drupal\apiservices\ApiResponseInterface
   *   The cached response, with updated headers.
   */
  protected function updateCachedResponse(ApiResponseInterface $cached, ResponseInterface $response) {
    if ($response->getStatusCode() != 304) {
      throw new ApiServiceException('A \'304 Not Modified\' response is required to update a cached response');
    }

    $headers = [];
    foreach ($response->getHeaders() as $name => $value) {
      $name = strtolower($name);
      $headers[$name] = implode(', ', $value);
    }

    // Merge the remaining end-to-end headers.
    $headers = array_merge($cached->getHeaders(), $headers);
    return $cached->withHeaders($headers);
  }

  /**
   * Determines a more specific EndpointException from a client exception.
   *
   * @param \GuzzleHttp\Exception\RequestException $e
   *   The exception from an unsuccessful request.
   *
   * @return \Drupal\apiservices\Exception\EndpointException
   *   The wrapped HTTP client exception.
   */
  protected function wrapException(RequestException $e) {
    $wrapper = new EndpointException('An unknown error occurred while sending the request, the client may not be properly configured or the network connection may be unavailable', NULL, $e);
    if ($e->hasResponse()) {
      $response = new GuzzleResponse($e->getResponse());
      if ($response->getStatusCode() >= 500) {
        // Generic 5xx (server error) response.
        $wrapper = new EndpointServerException('The API server encountered a problem and could not fulfill the request', $response, $e);
      }
      else if ($response->getStatusCode() == 403) {
        // Forbidden.
        $wrapper = new EndpointDeniedException('The request did not contain valid credentials or the client may be rate limited', $response, $e);
      }
      else if ($response->getStatusCode() == 404) {
        // Not found.
        $wrapper = new EndpointNotFoundException('A request was made to a resource that does not exist', $response, $e);
      }
      else {
        // Generic 4xx (client error) response.
        $wrapper = new EndpointRequestException('Received an invalid response due to a malformed request', $response, $e);
      }
    }
    return $wrapper;
  }

  /**
   * Sends an API request.
   *
   * @param \Drupal\apiservices\ApiProviderInterface $provider
   *   The configured API provider.
   * @param array $options
   *   (optional) Additional request options.
   *   - client: An array of settings passed to the underlying HTTP client.
   *   - refresh: A boolean value indicating whether to ignore the local cache
   *     and send the request with an additional 'If-Modified-Since' header. If
   *     the response has not been modified, the cached data is then returned.
   *
   * @return \Drupal\apiservices\ApiResponseInterface
   *   The API response.
   *
   * @throws \Drupal\apiservices\Exception\EndpointException
   */
  public function request(ApiProviderInterface $provider, array $options = []) {
    $request = $provider->getRequest();

    // Do not automatically decode content so that this client can cache
    // compressed responses.
    $defaults = [
      'client' => ['decode_content' => FALSE],
      'refresh' => FALSE,
    ];
    $options = NestedArray::mergeDeep($defaults, $options);

    $cache = $this->getCachedResponse($provider, TRUE);
    if ($cache !== FALSE) {
      if ($cache->valid && !$options['refresh']) {
        return $cache->data;
      }
      $request = $request->withHeader('If-Modified-Since', $cache->data->getHeader('Date'));
    }

    if (extension_loaded('zlib')) {
      $request = $request->withHeader('Accept-Encoding', 'gzip');
    }

    $response = NULL;
    try {
      $response = $this->client->send($request, $options['client']);
      $response = $this->handleResponse($provider, $request, $response);
    }
    catch (RequestException $e) {
      throw $this->wrapException($e);
    }

    return $response;
  }

  /**
   * Sends multiple API requests concurrently (if possible).
   *
   * @param \Drupal\apiservices\ApiProviderInterface[] $providers
   *   A list of configured API providers.
   * @param array $options
   *   (optional) Additional request options:
   *   - client: An array of settings passed to the underlying HTTP client.
   *   - refresh: A boolean value indicating whether to ignore the local cache
   *     and send the request with an additional 'If-Modified-Since' header. If
   *     the response has not been modified, the cached data is then returned.
   *
   * @return array
   *   A list containing both responses and, if one or more requests failed,
   *   exceptions. The keys of the returned array will match the keys of the
   *   API provider list.
   */
  public function requestMultiple(array $providers, array $options = []) {
    $responses = [];

    $defaults = [
      'client' => ['options' => ['decode_content' => FALSE]],
      'refresh' => FALSE,
    ];
    $options = NestedArray::mergeDeep($defaults, $options);

    // Split up requests that have a cached response and those that actually
    // need to be sent. Due to a bug in Guzzle however, the responses to sent
    // requests are not keyed using the input array. Therefore we need to
    // reorder the list of requests and store a map of new keys to old keys.
    $requests = [];
    $request_map = [];
    foreach ($providers as $key => $provider) {
      $request = $provider->getRequest();

      $cache = $this->getCachedResponse($provider, TRUE);
      if ($cache !== FALSE) {
        if ($cache->valid && !$options['refresh']) {
          $responses[$key] = $cache->data;
          continue;
        }
        $request = $request->withHeader('If-Modified-Since', $cache->data->getHeader('Date'));
      }

      if (extension_loaded('zlib')) {
        $request = $request->withHeader('Accept-Encoding', 'gzip');
      }

      $requests[] = $request;
      $request_map[] = $key;
    }

    // Guzzle does not like to wait for a promise that does nothing.
    if (empty($requests)) {
      return $responses;
    }

    // @todo The bug itself has actually been fixed, but did not make it into
    // Drupal's 8.0.0 release since it was not included in a stable Guzzle
    // release at the time (see https://github.com/guzzle/guzzle/pull/1203).
    $results = Pool::batch($this->client, $requests, $options['client']);

    // Process the responses from requests that were sent.
    foreach ($requests as $i => $request) {
      $response = $results[$i];
      $key = $request_map[$i];
      if ($response instanceof \Exception) {
        $responses[$key] = $this->wrapException($response);
      }
      else {
        $responses[$key] = $this->handleResponse($providers[$key], $request, $response);
      }
    }

    return $responses;
  }

}
