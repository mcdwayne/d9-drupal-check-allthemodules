<?php

namespace Drupal\flipbook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Flipbook entity.
 *
 * @ingroup flipbook
 */
interface FlipbookInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
