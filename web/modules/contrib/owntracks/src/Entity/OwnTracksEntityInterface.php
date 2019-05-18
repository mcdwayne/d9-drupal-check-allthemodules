<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the owntracks entity interface.
 */
interface OwnTracksEntityInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets a location array.
   *
   * @return array
   *   A numeric array containing a latitude and longitude value.
   */
  public function getLocation();

}
