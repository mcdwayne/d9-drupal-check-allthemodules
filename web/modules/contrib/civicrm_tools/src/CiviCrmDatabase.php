<?php

namespace Drupal\civicrm_tools;

use Drupal\Core\Database\Database;

/**
 * Class CiviCrmGroup.
 */
class CiviCrmDatabase implements CiviCrmDatabaseInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($query, array $args = [], array $options = []) {
    // @todo test database settings
    // Get a connection to the CiviCRM database.
    Database::setActiveConnection('civicrm');
    $db = Database::getConnection();
    // @todo check possible security issue here
    // @todo implement options
    $query = $db->query($query, $args);
    $result = $query->fetchAll();
    // Switch back to the default database.
    Database::setActiveConnection();
    return $result;
  }

}
