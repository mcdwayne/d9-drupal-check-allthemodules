<?php

namespace Drupal\preserve_page_cache\StackMiddleware;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\page_cache\StackMiddleware\PageCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A variant of Drupal's page cache which ignores cache tags.
 *
 * By not setting cache tags when writing cache entries, caches are not
 * invalidated by cache tags but only expire after a certain time.
 *
 * @see \Drupal\preserve_page_cache\NoTagsPageCacheServiceProvider
 */
class NoTagsPageCache extends PageCache {

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, AliasManagerInterface $alias_manager) {
    parent::__construct($http_kernel, $cache, $request_policy, $response_policy);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function set(Request $request, Response $response, $expire, array $tags) {
    // Just do not pass on the cache tags. Instead we set an expire header which
    // is not permanent, but matches the max-age cache settings.
    if ($response->getMaxAge() > 0) {
      $expire = REQUEST_TIME + $response->getMaxAge();
    }

    // Keep the node tag, so that invalidation works when editing an article.
    $path = $this->aliasManager->getPathByAlias($request->getPathInfo());
    if (preg_match('/node\/(\d+)/', $path, $m)) {
      $tags = ["node:{$m[1]}"];
    }

    return parent::set($request, $response, $expire, $tags);
  }

}
