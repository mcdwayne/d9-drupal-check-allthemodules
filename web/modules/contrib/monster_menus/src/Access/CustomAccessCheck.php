<?php

namespace Drupal\monster_menus\Access;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\monster_menus\Entity\MMTree;
use Symfony\Component\Routing\Route;

/**
 * Override Drupal\Core\Access\CustomAccessCheck to handle the case where a
 * route has an unresolved {mm_tree} parameter.
 */
class CustomAccessCheck extends \Drupal\Core\Access\CustomAccessCheck {

  /**
   * @inheritdoc
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // If {mm_tree} is present in the path but not supplied, use the current
    // page or the homepage.
    if (isset($route->getOption('parameters')['mm_tree']) && empty($route_match->getParameter('mm_tree'))) {
      mm_parse_args($mmtids, $oarg_list, $this_mmtid);
      $route_match->getParameters()->set('mm_tree', MMTree::load($this_mmtid ?: mm_home_mmtid()));
    }
    return parent::access($route, $route_match, $account);
  }

}
