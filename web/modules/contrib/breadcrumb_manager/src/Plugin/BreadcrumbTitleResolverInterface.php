<?php

namespace Drupal\breadcrumb_manager\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for Breadcrumb title resolver plugins.
 */
interface BreadcrumbTitleResolverInterface extends PluginInspectionInterface {

  /**
   * Get title.
   *
   * @param string $path
   *   The path.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The Route match.
   *
   * @return string
   *   The resolved title.
   */
  public function getTitle($path, Request $request, RouteMatchInterface $route_match);

  /**
   * Set active.
   *
   * @param bool $active
   *   A boolean indicating whether the title resolver is active or not.
   */
  public function setActive($active = TRUE);

  /**
   * Is active.
   *
   * @return bool
   *   A boolean indicating whether the title resolver is active or not.
   */
  public function isActive();

}
