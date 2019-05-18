<?php

namespace Drupal\annotations;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Annotations entity.
 *
 *
 * @ingroup annotations
 */
interface AnnotationsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
