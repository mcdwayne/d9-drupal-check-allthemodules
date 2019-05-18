<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\poll\PollStorage as ContribPollStorage;

/**
 * Storage handler for files.
 */
class PollStorage extends ContribPollStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait;

}
