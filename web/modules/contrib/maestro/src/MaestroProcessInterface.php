<?php

namespace Drupal\maestro;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Process entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup content_entity_example
 */
interface MaestroProcessInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
