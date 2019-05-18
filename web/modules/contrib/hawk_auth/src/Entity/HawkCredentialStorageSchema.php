<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Entity\HawkCredentialStorageSchema.
 */

namespace Drupal\hawk_auth\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Manages hawk credentials' database schema.
 */
class HawkCredentialStorageSchema extends SqlContentEntityStorageSchema implements HawkCredentialStorageSchemaInterface {

}
