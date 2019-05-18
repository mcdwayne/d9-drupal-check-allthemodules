<?php

namespace Drupal\library\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * Library transaction migration.
 *
 * @MigrateDestination(
 *   id = "library_transaction",
 *   provider = "library"
 * )
 */
class LibraryTransaction extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected static function getEntityTypeId($plugin_id) {
    return 'library_transaction';
  }

}
