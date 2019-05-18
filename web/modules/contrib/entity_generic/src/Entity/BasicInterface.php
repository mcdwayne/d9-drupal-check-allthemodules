<?php

namespace Drupal\entity_generic\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for basic entities.
 */
interface BasicInterface extends ContentEntityInterface, EntityChangedInterface, EntityCreatedInterface, EntityArchivedInterface, EntityDeletedInterface, EntityApprovedInterface {

}
