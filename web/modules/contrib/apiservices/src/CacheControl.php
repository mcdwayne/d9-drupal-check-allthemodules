<?php

/**
 * @file
 * Contains \Drupal\apiservices\CacheControl.
 */

namespace Drupal\apiservices;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Implements utility functions for managing an HTTP response cache.
 */
class CacheControl {

  /**
   * A default list of hop-by-hop headers.
   *
   * These headers are meaningful only for a single transport-level connection,
   * and are not stored by caches or forwarded by proxies.
   *
   * NOTE: RFC 7230 now requires all hop-by-hop headers to be included in the
   * 'Connection' header field. This list is for backwards-compatibility only.
   *
   * @link http://tools.ietf.org/html/rfc2616#section-13.5.1
   *
   * @var array
   */
  public static $HOP_BY_HOP_HEADERS = [
    'Connection',
    'Keep-Alive',
    'Proxy-Authenticate',
    'Proxy-Authorization',
    'TE',
    'Trailers',
    'Transfer-Encoding',
    'Upgrade',
  ];

  /**
   * Determines the age of the response. This is a conservative estimate due to
   * network-imposed delays.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   * @param int $response_time
   *   A timestamp indicating when the response was received.
   *
   * @return int
   *   The sum of the time a response has been in each cache along the path
   *   of the origin server, plus the amount of time it has been in transit.
   *
   * @link https://tools.ietf.org/html/rfc7234#section-4.2.3 Calculating Age
   *
   * @see CacheControl::getResponseLifetime()
   */
  public static function getResponseAge(ResponseInterface $response, $response_time) {
    $now = time();

    $age = 0;
    if ($response->hasHeader('Age')) {
      $age = (int) $response->getHeaderLine('Age');
    }

    $date = FALSE;
    if ($response->hasHeader('Date')) {
      $date = strtotime($response->getHeaderLine('Date'));
    }
    if ($date === FALSE) {
      // All cacheable responses must be stored with their creation date.
      throw new \InvalidArgumentException("Response must contain a 'Date' header");
    }

    $apparent_age = max(0, $response_time - $date);
    $corrected_received_age = max($apparent_age, $age);
    $response_delay = $response_time - REQUEST_TIME;
    $corrected_initial_age = $corrected_received_age + $response_delay;

    return $corrected_initial_age + ($now - $response_time);
  }

  /**
   * Gets the cache lifetime of a response from its headers.
   *
   * WARNING: This function may return boolean FALSE or a non-boolean value
   * that evaluates to FALSE.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   An HTTP response.
   *
   * @return int|FALSE
   *   The amount of time that the response should be cached, or FALSE if no
   *   cache-control headers appeared in the response.
   *
   * @link https://tools.ietf.org/html/rfc7234#section-4.2.1 Calculating Freshness Lifetime
   *
   * @see CacheControl::getResponseAge()
   */
  public static function getResponseLifetime(ResponseInterface $response) {
    $date = FALSE;
    if ($response->hasHeader('Date')) {
      $date = strtotime($response->getHeaderLine('Date'));
    }
    if ($date === FALSE) {
      // All cacheable responses must be stored with their creation date.
      throw new \InvalidArgumentException("Response must contain a 'Date' header");
    }

    // RFC 7234: If a response includes a Cache-Control field with the max-age
    // directive (Section 5.2.2.8), a recipient MUST ignore the Expires field.
    if ($response->hasHeader('Cache-Control')) {
      $cache_control = $response->getHeader('Cache-Control');
      foreach ($cache_control as $directive) {
        if (strpos($directive, '=') === FALSE) {
          continue;
        }
        list($key, $value) = explode('=', trim($directive), 2);
        if ($key == 'max-age') {
          return (int) $value;
        }
      }
    }

    if ($response->hasHeader('Expires')) {
      $expire = strtotime($response->getHeaderLine('Expires'));
      if ($expire === FALSE) {
        // RFC 7234: A cache recipient MUST interpret invalid date formats,
        // especially the value "0", as representing a time in the past
        // (i.e., "already expired").
        $expire = $date - 1;
      }
      return $expire - $date;
    }

    return FALSE;
  }

  /**
   * Determines if the origin server has included a constraining cache-control
   * directive in the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   *
   * @return bool
   *   Returns TRUE if the response is not constrained by a cache-control
   *   directive.
   *
   * @link http://tools.ietf.org/html/rfc2616#section-13.4 Response Cacheability
   *
   * @see CacheControl::isResponseCacheable()
   */
  public static function hasCacheConstraint(ResponseInterface $response) {
    if ($response->hasHeader('Cache-Control')) {
      $cache_control = $response->getHeader('Cache-Control');
      foreach ($cache_control as $directive) {
        if ($directive == 'no-store') {
          return TRUE;
        }
      }
    }
    if ($response->hasHeader('Vary')) {
      if ($response->getHeaderLine('Vary') == '*') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines if a request is cacheable.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The HTTP request.
   *
   * @return bool
   *   The request cacheability. If FALSE, the response to this request must
   *   not be cached either.
   */
  public static function isRequestCacheable(RequestInterface $request) {
    if ($request->getMethod() != 'GET' && $request->getMethod() != 'HEAD') {
      return FALSE;
    }
    if ($request->hasHeader('Authorization')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Determines if a response contains headers that prevent caching.
   *
   * REMINDER: If the request is not cacheable, neither is the response!
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   * @param int $response_time
   *   A timestamp indicating when the response was received. This parameter is
   *   ignored if $strict is FALSE.
   * @param bool $strict
   *   (optional) If FALSE, a check for responses with no cache validator and
   *   no explicit expiration time is skipped. These responses are not expected
   *   to be cached, but certain caches MAY violate this expectation. Defaults
   *   to TRUE.
   *
   * @return bool
   *   The response cacheability.
   *
   * @link http://tools.ietf.org/html/rfc7234#section-3
   *
   * @see CacheControl::isRequestCacheable()
   */
  public static function isResponseCacheable(ResponseInterface $response, $response_time, $strict = TRUE) {
    if (!static::isResponseCodeCacheable($response)) {
      return FALSE;
    }
    if ($strict) {
      $expired = static::isResponseExpired($response, $response_time);
      if ($expired && !$response->hasHeader('ETag')) {
        return FALSE;
      }
    }
    if (static::hasCacheConstraint($response)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Determines if the response status code is cacheable.
   *
   * @param \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   *
   * @return bool
   *   If the status code is cacheable, TRUE.
   *
   * @link https://tools.ietf.org/html/rfc7231#section-6.1
   */
  public static function isResponseCodeCacheable(ResponseInterface $response) {
    $cacheable_codes = [200, 203, 204, 206, 300, 301, 404, 405, 410, 414, 501];
    if (in_array($response->getStatusCode(), $cacheable_codes)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Uses the response's age and cache lifetime to determine if it has expired.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response.
   * @param int $response_time
   *   A timestamp indicating when the response was received.
   *
   * @return bool
   *   Returns TRUE if the response has expired or did not contain a cache-
   *   control directive.
   */
  public static function isResponseExpired(ResponseInterface $response, $response_time) {
    $age = static::getResponseAge($response, $response_time);
    $lifetime = static::getResponseLifetime($response);
    if ($lifetime === FALSE) {
      // Technically some heuristics can be done here, like using a fraction of
      // the time period between 'Last-Modified' and 'Date' headers.
      return TRUE;
    }
    return $lifetime <= $age;
  }

}
