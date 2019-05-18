<?php

namespace Drupal\ext_redirect\Service;

/**
 * Class CacheableRedirectRuleMatcher.
 */
class CacheableRedirectRuleMatcher extends RedirectRuleMatcher {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * Constructs a new CacheableRedirectRuleMatcher object.
   */
  public function __construct(RedirectRuleRepository $ext_redirect_repository) {
    parent::__construct($ext_redirect_repository);
    $this->cache = \Drupal::cache();
  }

  public function lookup($host, $path = '') {
    $rule = NULL;
    $cid = 'ext_redirect:' . $host . $path;

    $rule = $this->getCachedRule($cid);
    if ($rule) {
      return $rule;
    }

    $rule = parent::lookup($host, $path);

    if ($rule) {
      $this->cache->set($cid, $rule);
    }

    return $rule;
  }

  private function getCachedRule($cid) {
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }
    return NULL;
  }

}
