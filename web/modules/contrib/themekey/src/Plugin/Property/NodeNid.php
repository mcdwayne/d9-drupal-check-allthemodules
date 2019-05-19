<?php

/**
 * @file
 * Contains \Drupal\themekey\Plugin\Property\DrupalRouteName.
 */

namespace Drupal\themekey\Plugin\Property;

use Drupal\themekey\PropertyBase;

/**
 * Provides a 'node id' property.
 *
 * @Property(
 *   id = "node:nid",
 *   name = @Translation(""),
 *   description = @Translation(""),
 *   page_cache_compatible = TRUE,
 * )
 */
class NodeNid extends PropertyBase {

  /**
   * @return array
   *   array of drupal:route_name values
   */
  public function getValues() {
    $node = $this->getRouteMatch()->getParameter('node');
    return is_null($node) ?  : array($node->get('nid')->value);
  }
}
