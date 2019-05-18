<?php

namespace Drupal\dcat_import;

use Drupal\Core\Database\Connection;
use Drupal\migrate\MigrateMessageInterface;

/**
 * Class DcatImportLogMigrateMessage.
 *
 * @package Drupal\dcat_import
 */
class DcatImportLogMigrateMessage implements MigrateMessageInterface {

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Import start time as unix timestamp.
   *
   * @var int
   */
  protected $startDate;

  /**
   * Current migration id.
   *
   * @var string
   */
  protected $migration;

  /**
   * Dcat source id.
   *
   * @var string
   */
  protected $dcatSource;

  /**
   * DcatImportLogMigrateMessage constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database.
   * @param int $start_date
   *   Import start date.
   * @param string $dcat_source
   *   Dcat source id.
   * @param string $migration
   *   Current migration id.
   */
  public function __construct(Connection $database, $start_date, $dcat_source, $migration) {
    $this->database = $database;
    $this->startDate = $start_date;
    $this->dcatSource = $dcat_source;
    $this->migration = $migration;
  }

  /**
   * Save a message from the migration to the db table.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   */
  public function display($message, $type = 'status') {
    $this->database->insert('dcat_import_log')
      ->fields([
        'import_start' => $this->startDate,
        'source' => $this->dcatSource,
        'migration' => $this->migration,
        'message' => $message,
        'type' => $type,
      ])
      ->execute();
  }

}
