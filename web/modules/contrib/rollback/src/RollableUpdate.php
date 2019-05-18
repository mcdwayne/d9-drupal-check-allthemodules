<?php

namespace Drupal\rollback;

/**
 * Class RollableUpdate.
 */
abstract class RollableUpdate {

  /**
   * Schema version of the update.
   *
   * @var int
   */
  protected $schema;

  /**
   * Perform the update.
   */
  abstract public function up();

  /**
   * Rollback the update.
   */
  abstract public function down();

  /**
   * Returns the schema value.
   *
   * @return int
   *   Schema value.
   */
  public function getSchema() {
    return $this->schema;
  }

}
