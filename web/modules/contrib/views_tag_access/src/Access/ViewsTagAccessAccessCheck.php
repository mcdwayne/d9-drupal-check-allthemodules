<?php

namespace Drupal\views_tag_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for views collection list page.
 */
class ViewsTagAccessAccessCheck implements AccessInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Check that this user should have access to the views collection list page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    // If this users has the administer views permission they have access.
    if ($account->hasPermission('administer views')) {
      return AccessResult::allowed();
    }

    // If the account can create views, then they also have access.
    if ($account->hasPermission('create views')) {
      return AccessResult::allowed();
    }

    // Finally, if the user has any access on any of the tag permissions, they
    // can access the page.
    $tags = $this->configFactory->get('views_tag_access.settings')->get('tags');
    $permissions = ['administer', 'update', 'duplicate', 'enabled', 'disable', 'delete'];
    foreach ($tags as $tag) {
      foreach ($permissions as $permission) {
        if ($account->hasPermission("{$permission} views tagged {$tag}")) {
          return AccessResult::allowed();
        }
      }
    }

    // If we've got this far they don't have access.
    return AccessResult::forbidden();
  }

}
