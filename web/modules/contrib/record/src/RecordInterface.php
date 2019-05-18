<?php

namespace Drupal\record;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an Data record entity.
 */
interface RecordInterface extends ContentEntityInterface, EntityChangedInterface {

}
