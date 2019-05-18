<?php

namespace Drupal\dcat_import;

/**
 * Class DcatImportUiLogMigrateMessage.
 *
 * @package Drupal\dcat_import
 */
class DcatImportUiLogMigrateMessage extends DcatImportLogMigrateMessage {

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   */
  public function display($message, $type = 'status') {
    parent::display($message, $type);

    drupal_set_message($message, $type);
  }

}
