<?php

namespace Drupal\cache_alter\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\page_cache\StackMiddleware\PageCache;

/**
 * Extending PageCache.
 */
class CacheAlter extends PageCache {

  /**
   * {@inheritdoc}
   */
  protected function set(Request $request, Response $response, $expire, array $tags) {
    $cid = $this->getCacheId($request);
    if (FALSE) {
      $content = $response->getContent();
      $response->setContent($content);
    }
    $this->cache->set($cid, $response, $expire, $tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheId(Request $request) {
    $uri = $request->getSchemeAndHttpHost() . $request->getRequestUri();

    $path = strstr("$uri?", '?', TRUE);
    $query = strstr($uri, '?');
    if ($pos = strpos($query, 'utm_')) {
      $query = substr($query, 0, $pos - 1);
    }

    $cookie = '';
    if (isset($_COOKIE['cache_context']) && strlen($_COOKIE['cache_context'])) {
      $cookie = "-" . htmlentities($_COOKIE['cache_context'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $cid_parts = [
      "{$path}{$query}",
      $request->getRequestFormat() . "$cookie",
    ];
    return implode(':', $cid_parts);
  }

}
