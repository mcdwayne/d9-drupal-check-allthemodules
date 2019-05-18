<?php

namespace Drupal\googlemaps\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface GoogleMapInterface extends ConfigEntityInterface {

  /**
   * Entity type.
   */
  const ENTITY_TYPE = 'googlemap';

}
