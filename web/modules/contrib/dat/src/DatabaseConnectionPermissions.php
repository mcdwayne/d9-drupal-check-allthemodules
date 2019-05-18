<?php

namespace Drupal\dat;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dat\Entity\DatabaseConnection;
use Drupal\dat\Entity\DatabaseConnectionInterface;

/**
 * Provides dynamic permissions of the dat module.
 *
 * @see dat.permissions.yml
 */
class DatabaseConnectionPermissions {

  use StringTranslationTrait;

  /**
   * Get Database Connection permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach (DatabaseConnection::loadMultiple() as $connection) {
      $permissions += $this->buildPermissions($connection);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of permissions for a Database Connection.
   *
   * @param \Drupal\dat\Entity\DatabaseConnectionInterface $connection
   *   The Database Connection.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(DatabaseConnectionInterface $connection) {
    $id = $connection->id();
    $args = ['%connection' => $connection->label()];

    return [
      "use adminer to manage the $id database" => [
        'title' => $this->t('%connection: Use Adminer to manage the database', $args),
        'restrict access' => TRUE,
      ],
      "use editor to manage the $id database" => [
        'title' => $this->t('%connection: Use Editor to manage the database', $args),
        'restrict access' => TRUE,
      ],
    ];
  }

}
