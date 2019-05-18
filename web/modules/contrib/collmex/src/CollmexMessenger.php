<?php

namespace Drupal\collmex;

use Drupal\migrate\Plugin\MigrationInterface;

class CollmexMessenger {

  /** @var bool */
  private $debugMode;

  /** @var \Drupal\migrate\Plugin\MigrationInterface */
  protected $migration;

  /** @var array */
  protected $sourceIdValues;

  /**
   * CollmexMessenger constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param array $sourceIdValues
   *   As the migration iterates the sourceIdValues in different places
   *   depending on import or rollback, and the messages table is indexed by
   *   these values, we need them here.
   */
  public function __construct(\Drupal\migrate\Plugin\MigrationInterface $migration, array $sourceIdValues) {
    $this->migration = $migration;
    $this->sourceIdValues = $sourceIdValues;
  }

  public function saveMessage($message, $level = MigrationInterface::MESSAGE_INFORMATIONAL) {
    $this->migration->getIdMap()
      ->saveMessage($this->sourceIdValues, $message, $level);
  }

  public function saveDebugMessage($message) {
    if ($this->isDebugMode()) {
      $this->saveMessage($message);
    }
  }

  protected function isDebugMode() {
    if (!isset($this->debugMode)) {
      $config = \Drupal::config('collmex.settings');
      $this->debugMode = (bool) $config->get('debug');
    }
    return $this->debugMode;
  }

}
