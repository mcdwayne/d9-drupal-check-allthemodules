<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\file\FileStorage as CoreFileStorage;

/**
 * Storage handler for files.
 */
class FileStorage extends CoreFileStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait;

}
