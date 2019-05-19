<?php

namespace Drupal\views_restricted\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_restricted\ViewsRestrictedInterface;
use Symfony\Component\Routing\Route;

class AccessController implements AccessInterface {

  /**
   * @param \Drupal\views_restricted\ViewsRestrictedInterface|NULL $views_restricted
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(ViewsRestrictedInterface $views_restricted, Route $route, RouteMatch $route_match) {
    /** @var \Drupal\views\ViewEntityInterface|null $view */
    $view = $route_match->getParameter('view');
    $display_id = $route_match->getParameter('display_id');
    $type = $route_match->getParameter('type');
    $accessResult = $views_restricted->access($view, $display_id, $type, NULL, NULL, NULL, $route, $route_match);
    return $accessResult;
  }

}
