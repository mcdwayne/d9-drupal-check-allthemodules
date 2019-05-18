<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Entity\HawkCredentialStorage.
 */

namespace Drupal\hawk_auth\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Manages hawk credentials' entities.
 */
class HawkCredentialStorage extends SqlContentEntityStorage implements HawkCredentialStorageInterface {

}
