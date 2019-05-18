<?php

namespace Drupal\ext_redirect\Service;

use Drupal\ext_redirect\Entity\RedirectRule;

/**
 * Interface RedirectRuleRepositoryInterface.
 */
interface RedirectRuleRepositoryInterface {

  /**
   * Gets redirect rule for host alias without specified path.
   *
   * @param $host string host name like alias.com
   *
   * @return RedirectRule|null
   *    Redirect rule entity or null if no rule found
   */
  public function getRuleForHostWithoutPath($host);

  /**
   * Gets available redirect rules for specified host.
   *
   * @param $host string host name like alias.com
   *
   * @return array
   *    An array of RedirectRule entity. Empty array if nothing found.
   */
  public function getHostRules($host);

  /**
   * Gets available redirect rules for any host .
   *
   * @return array
   *    An array of RedirectRule entity. Empty array if nothing found.
   */
  public function getGlobalRules();

}
