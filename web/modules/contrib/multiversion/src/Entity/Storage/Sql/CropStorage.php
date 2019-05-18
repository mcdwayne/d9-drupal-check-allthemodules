<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;
use Drupal\crop\CropStorage as ContribCropStorage;

/**
 * Image crop storage class.
 */
class CropStorage extends ContribCropStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait;

}
