<?php

namespace Drupal\ajax_add_to_cart\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ReloadCommand.
 *
 * @package Drupal\ajax_add_to_cart\Ajax
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
