<?php

namespace Drupal\ext_redirect\Service;

use Drupal\ext_redirect\Entity\RedirectRule;

/**
 * Interface RedirectRuleMatcherInterface.
 */
interface RedirectRuleMatcherInterface {

  /**
   * Lookup for a redirect rule.
   *
   * @param $host string
   *    Host extracted from request URI
   * @param $path string
   *    URI path (maybe also path alias)
   *
   * @return RedirectRule|null
   *    Return RedirectRule entity if any rule is matched.
   */
  public function lookup($host, $path = '');


}
