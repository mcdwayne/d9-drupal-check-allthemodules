<?php

namespace Drupal\ajax_login\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ReloadCommand.
 *
 * @package Drupal\ajax_login\Ajax
 */
class ReloadCommand implements CommandInterface {

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'reload',
    ];
  }

}
