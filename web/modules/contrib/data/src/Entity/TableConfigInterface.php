<?php

namespace Drupal\data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Data Table entities.
 */
interface TableConfigInterface extends ConfigEntityInterface {

  /**
   * Check if data table exists.
   *
   * @return bool
   */
  public function exists();

  /**
   * Create data table.
   *
   * @param array $table_definition
   * @return mixed
   */
  public function createTable();
}
