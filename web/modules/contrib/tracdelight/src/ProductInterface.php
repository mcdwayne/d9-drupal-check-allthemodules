<?php

/**
 * @file
 * Contains Drupal\tracdelight\ProductInterface.
 */

namespace Drupal\tracdelight;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Product entities.
 *
 * @ingroup tracdelight
 */
interface ProductInterface extends ContentEntityInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

}
