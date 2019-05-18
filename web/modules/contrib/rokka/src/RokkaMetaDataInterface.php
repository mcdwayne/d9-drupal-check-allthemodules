<?php

namespace Drupal\rokka;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Contact entity.
 *
 * @ingroup content_entity_example
 */
interface RokkaMetaDataInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
