<?php

namespace Drupal\library;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Library item entities.
 *
 * @ingroup library
 */
interface LibraryItemInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  const IN_CIRCULATION = 0;
  const REFERENCE_ONLY = 1;

  const ITEM_AVAILABLE = 0;
  const ITEM_UNAVAILABLE = 1;

}
