<?php

namespace Drupal\item_lot\Access;

use Drupal\aggregator\ItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on lot control status of current item.
 */
class LotControlStatusCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route, ItemInterface $item = NULL) {
    $required_status = filter_var($route->getRequirement('_item_is_lot_controlled'), FILTER_VALIDATE_BOOLEAN);
    $actual_status = $item->get('lot_control')->value;
    return AccessResult::allowedIf($required_status === $actual_status);
  }

}
