<?php

namespace Drupal\file_utility;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 *
 * @ingroup content_entity_example
 */
interface FileUtilityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
