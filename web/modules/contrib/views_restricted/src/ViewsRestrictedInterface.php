<?php

namespace Drupal\views_restricted;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\Routing\Route;

/**
 * Interface definition for views_restricted plugins.
 */
interface ViewsRestrictedInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * @param \Drupal\views\ViewEntityInterface|null $view
   * @param string|null $display_id
   * @param string|null $type
   * @param string|null $table
   * @param string|null $field
   * @param string|null $alias
   * @param \Symfony\Component\Routing\Route|null $route
   * @param \Drupal\Core\Routing\RouteMatch|null $route_match
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(ViewEntityInterface $view, $display_id = NULL, $type = NULL, $table = NULL, $field = NULL, $alias = NULL, Route $route = NULL, RouteMatch $route_match = NULL);

}
