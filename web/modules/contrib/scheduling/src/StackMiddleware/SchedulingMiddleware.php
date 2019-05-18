<?php

namespace Drupal\scheduling\StackMiddleware;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Site\Settings;
use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class SchedulingMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a PageCache object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $request_policy
   *   A policy rule determining the cacheability of a request.
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $response_policy
   *   A policy rule determining the cacheability of the response.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Only allow page caching on master request.
    if ($type === static::MASTER_REQUEST) {
      $response = $this->httpKernel->handle($request, $type, $catch);
      // If we have a specific Expires header value that does not match the
      // default value, modify Cache-control max-age directive to accurately
      // reflect time (in seconds) until cached page cache response expires.
      if ($response->headers->get('Expires') !== 'Sun, 19 Nov 1978 05:00:00 GMT' && $expires_timestamp = $response->headers->get('X-Expires-Timestamp')) {
        $now = new DrupalDateTime();
        $expires_in = $expires_timestamp - $now->getTimestamp();
        if ($response->headers->hasCacheControlDirective('max-age')) {
          $response->headers->addCacheControlDirective('max-age', $expires_in);
        }
      }
      return $response;
    } else {
      return $this->httpKernel->handle($request, $type, $catch);
    }
  }

}
