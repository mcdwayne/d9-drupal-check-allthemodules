<?php

namespace Drupal\grnhse;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Job entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup grnhse
 */
interface JobInterface extends ContentEntityInterface, EntityChangedInterface {

}
