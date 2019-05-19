<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_extra\Entity\ContentEntityBase as EEContentEntityBase;
use Drupal\entity_extra\Entity\EntityCreatedTrait;
use Drupal\entity_extra\Entity\EntityOwnerTrait;

/**
 * Base class for the module's content entity types.
 */
class ContentEntityBase extends EEContentEntityBase implements ContentEntityInterface {
  use EntityCreatedTrait;
  use EntityChangedTrait;
  use EntityOwnerTrait;
}
