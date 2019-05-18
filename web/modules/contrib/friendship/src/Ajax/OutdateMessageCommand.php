<?php

namespace Drupal\friendship\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class OutdateMessageCommand.
 *
 * @package Drupal\friendship\Ajax
 */
class OutdateMessageCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'outdateMessage',
    ];
  }

}
