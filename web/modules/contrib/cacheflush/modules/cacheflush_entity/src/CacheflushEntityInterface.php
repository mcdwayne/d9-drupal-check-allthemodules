<?php

namespace Drupal\cacheflush_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cacheflush entities.
 *
 * @ingroup cacheflush_entity
 */
interface CacheflushEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
