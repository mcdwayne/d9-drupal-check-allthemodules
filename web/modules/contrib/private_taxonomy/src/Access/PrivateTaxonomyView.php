<?php

namespace Drupal\private_taxonomy\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\Routing\Route;

/**
 * Determines access to the vocabulary list page.
 */
class PrivateTaxonomyView implements AccessInterface {

  protected $currentUser;

  /**
   * The construct.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The loged user.
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route) {
    $permission
      = $this->currentUser->hasPermission('administer own taxonomy') ||
      $this->currentUser->hasPermission('view private taxonomies') ||
      $this->currentUser->hasPermission('administer taxonomy');
    if ($permission) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
