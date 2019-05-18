<?php

namespace Drupal\og_sm_path;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Site manager path helper methods.
 */
class OgSmPath {

  /**
   * Helper to check if the given account has access to change the Site path.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The Site to change the path for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account to check the access for.
   *
   * @return bool
   *   Whether the user has access or not.
   */
  public static function changeAccess(NodeInterface $site, AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    if ($account->hasPermission('change all site paths')) {
      return TRUE;
    }

    if ($account->hasPermission('change own site paths') && $site->getOwner()->id() === $account->id()) {
      return TRUE;
    }

    /* @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = \Drupal::service('og.access');
    return $og_access->userAccess($site, 'change site path', $account, FALSE, TRUE)->isAllowed();
  }

  /**
   * Returns the site path manager instance.
   *
   * @return \Drupal\og_sm_path\SitePathManagerInterface
   *   The site path manager service.
   */
  public static function sitePathManager() {
    return \Drupal::service('og_sm.path.site_path_manager');
  }

}
