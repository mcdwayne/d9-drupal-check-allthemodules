<?php

namespace Drupal\job;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for jobs.
 */
class JobStorage extends SqlContentEntityStorage implements JobStorageInterface {

}
