<?php

/**
 * @file
 * Contains \Drupal\themekey\Plugin\Property\DrupalRouteName.
 */

namespace Drupal\themekey\Plugin\Property;

use Drupal\themekey\PropertyBase;

/**
 * Provides a 'query param' property.
 *
 * @Property(
 *   id = "drupal:route_name",
 *   name = @Translation(""),
 *   description = @Translation(""),
 *   page_cache_compatible = TRUE,
 * )
 */
class DrupalRouteName extends PropertyBase {

  /**
   * @return array
   *   array of drupal:route_name values
   */
  public function getValues() {
    $routeName = $this->getRouteMatch()->getRouteName();
    return is_null($routeName) ?  : array($routeName);
  }
}
