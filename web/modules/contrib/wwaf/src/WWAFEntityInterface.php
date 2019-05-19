<?php

namespace Drupal\wwaf;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a WWAF Store entity.
 * @ingroup wwaf_entity
 */
interface WWAFEntityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}