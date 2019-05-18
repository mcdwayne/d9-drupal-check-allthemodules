<?php

namespace Drupal\content_entity_student;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup content_entity_example
 */
interface StContactInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>