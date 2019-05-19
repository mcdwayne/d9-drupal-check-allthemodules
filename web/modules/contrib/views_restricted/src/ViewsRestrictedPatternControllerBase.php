<?php

namespace Drupal\views_restricted;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\Routing\Route;

abstract class ViewsRestrictedPatternControllerBase extends ViewsRestrictedPluginBase {

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   * @param string|null $display_id
   * @param string $type
   * @param $table
   * @param string $field
   *
   * @param $alias
   *
   * @param \Symfony\Component\Routing\Route|null $route
   * @param \Drupal\Core\Routing\RouteMatch|null $route_match
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function getAccess(ViewEntityInterface $view, $display_id = NULL, $type = NULL, $table = NULL, $field = NULL, $alias = NULL, Route $route = NULL, RouteMatch $route_match = NULL) {
    $infoString = ViewsRestrictedHelper::makeInfoString($view, $display_id, $type, $table, $field, $alias);
    return $this->checkInfoString($infoString) ?
      AccessResult::allowed() : AccessResult::forbidden();
  }

  abstract protected function checkInfoString($infoString);

}
