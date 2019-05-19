<?php

namespace Drupal\trance;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;

/**
 * {@inheritdoc}
 *
 * Extended to support Multiversion.
 */
class TranceMultiversionStorage extends TranceStorage {

  use ContentEntityStorageTrait;

}
