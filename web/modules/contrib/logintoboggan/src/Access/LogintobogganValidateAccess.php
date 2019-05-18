<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Access\LogintobogganValidateAccess.
 */

namespace Drupal\logintoboggan\Access;

use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganValidateAccess implements RoutingAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_logintoboggan_validate_email_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $account->isAuthenticated() && arg(3) < REQUEST_TIME ? static::ALLOW : static::DENY;
  }
}
