<?php

namespace Drupal\ivw_integration;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Interface IvwLookupServiceInterface.
 *
 * @package Drupal\ivw_integration
 */
interface IvwLookupServiceInterface {

  /**
   * Automatically uses the current route to look up an IVW property.
   *
   * @param string $name
   *   The name of the IVW property to look up.
   * @param bool $parentOnly
   *   If set to TRUE, skips lookup on first level ivw_settings field.
   *
   * @return string
   *   The property value
   */
  public function byCurrentRoute($name, $parentOnly = FALSE);

  /**
   * Find value by route.
   *
   * @param string $name
   *   The name of the IVW property to look up.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route matching the entity (node, term) on which to look up
   *   properties.
   * @param bool $parentOnly
   *   If set to TRUE, skips lookup on first level ivw_settings field.
   *   This is used when determining which property the
   *   currently examined entity WOULD inherit if it had
   *   no property for $name in its own ivw settings.
   *
   * @return string
   *   The property value
   */
  public function byRoute($name, RouteMatchInterface $routeMatch, $parentOnly = FALSE);

  /**
   * Find value by entity.
   *
   * @param string $name
   *   The name of the IVW property to look up.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity (usually node) to look up the property on.
   * @param bool $parentOnly
   *   If set to TRUE, skips lookup on first level ivw_settings field.
   *   This is used when determining which property the
   *   currently examined entity WOULD inherit if it had
   *   no property for $name in its own ivw settings.
   *
   * @return string
   *   The property value
   */
  public function byEntity($name, ContentEntityInterface $entity, $parentOnly = FALSE);

  /**
   * Find value by entity.
   *
   * @param string $name
   *   The name of the IVW property to look up.
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to look up the property on.
   * @param bool $parentOnly
   *   If set to TRUE, skips lookup on first level ivw_settings field.
   *   This is used when determining which property the
   *   currently examined entity WOULD inherit if it had
   *   no property for $name in its own ivw settings.
   *
   * @return string
   *   The property value
   */
  public function byTerm($name, TermInterface $term, $parentOnly = FALSE);

  /**
   * Look up cache tags for the current route.
   *
   * @return array|\string[]
   *   An array of cache tags
   */
  public function getCacheTagsByCurrentRoute();

  /**
   * Look up cache tags for the provided route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The route, for which cache tags should be found.
   *
   * @return array|\string[]
   *   An array of cache tags
   */
  public function getCacheTagsByRoute(RouteMatchInterface $route);

}
