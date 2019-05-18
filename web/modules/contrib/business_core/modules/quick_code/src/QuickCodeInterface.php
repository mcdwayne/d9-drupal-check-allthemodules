<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a quick_code entity.
 */
interface QuickCodeInterface extends ContentEntityInterface, EntityChangedInterface  {

}