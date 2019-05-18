<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage handler for media entity.
 */
class MediaStorage extends SqlContentEntityStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait;

}
