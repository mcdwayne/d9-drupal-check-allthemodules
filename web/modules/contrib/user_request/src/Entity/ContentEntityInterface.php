<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Entity\ContentEntityInterface as CoreContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\entity_extra\Entity\EntityCreatedInterface;

/**
 * Interface for the module's entity types.
 */
interface ContentEntityInterface extends CoreContentEntityInterface, 
  EntityCreatedInterface,
  EntityChangedInterface, 
  EntityOwnerInterface {

}
