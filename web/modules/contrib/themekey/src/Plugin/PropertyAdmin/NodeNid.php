<?php

/**
 * @file
 * Contains \Drupal\themekey\Plugin\Property\DrupalRouteName.
 */

namespace Drupal\themekey\Plugin\PropertyAdmin;

use Drupal\themekey\PropertyAdminDigitBase;

/**
 * Administers a 'node id' property.
 *
 * @Property(
 *   id = "node:nid",
 * )
 */
class NodeNid extends PropertyAdminDigitBase {

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues() {
    return array();
  }
}
