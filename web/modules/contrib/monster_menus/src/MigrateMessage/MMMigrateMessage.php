<?php

namespace Drupal\monster_menus\MigrateMessage;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Defines a migrate message class which outputs nothing.
 */
class MMMigrateMessage implements MigrateMessageInterface {

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
  }

}
