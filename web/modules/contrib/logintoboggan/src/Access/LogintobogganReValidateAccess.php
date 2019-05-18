<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Access\LogintobogganReValidateAccess.
 */

namespace Drupal\logintoboggan\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganReValidateAccess implements RoutingAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_logintoboggan_revalidate_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $user = user_load(arg(2));
    return ($account->id() == $user->id() || $account->hasPermission('administer users'))
      ? static::ALLOW
      : static::DENY;
  }
}
