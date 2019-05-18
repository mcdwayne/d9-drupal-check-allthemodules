<?php

namespace Drupal\config_perms_context\Access;

use Drupal\config_perms\Access\ConfigPermsAccessCheck as ConfigPermsAccessCheckBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for custom_perms routes.
 */
class ConfigPermsAccessCheck extends ConfigPermsAccessCheckBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch) {
    /** @var \Drupal\context\ContextManager $context_manager */
    $context_manager = \Drupal::service('context.manager');
    $context_manager->getActiveReactions('config_perms');
    foreach ($context_manager->getActiveReactions('config_perms') as $reaction) {
      $config_perms = $reaction->execute();
      $route = str_replace('.', '___', $routeMatch->getRouteName());
      if (in_array($route, $config_perms['config_perms_forbiden'])) {
        return AccessResult::forbidden();
      }
      if (in_array($route, $config_perms['config_perms_allow'])) {
        return AccessResult::allowed();
      }
    }

    return parent::access($account, $routeMatch);
  }

}
