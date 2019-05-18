<?php

/**
 * @file
 * Contains \Drupal\Core\Block\BlockPluginInterface.
 */

namespace Drupal\feadmin\FeAdminTool;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the required interface for all feadmintool plugins.
 *
 * @ingroup feadmin
 * 
 * Sponsored by: www.freelance-drupal.com
 */
interface FeAdminToolInterface {



  /**
   * Indicates whether the feadmintool should be shown.
   *
   * This method allows base implementations to add general access restrictions
   * that should apply to all extending feadmintool plugins.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   *
   */
  public function access(AccountInterface $account);

  /**
   * Builds and returns the renderable array for this feadmintool plugin.
   *
   * If a feadmintool should not be rendered because it has no content, then
   * this method must also ensure to return no content: it must then only return
   * an empty array, or an empty array with #cache set (with cacheability
   * metadata indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the feadmintool.
   *
   * @see \Drupal\feadmin\FeAdminTooltool\FeAdminToolViewBuilder
   */
  public function build();
}
