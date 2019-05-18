<?php

namespace Drupal\competition;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for competition_entry entities.
 *
 * This extends the base storage class, adding required special handling for
 * competition_entry entities.
 */
class CompetitionEntryStorage extends SqlContentEntityStorage implements CompetitionEntryStorageInterface {

}
