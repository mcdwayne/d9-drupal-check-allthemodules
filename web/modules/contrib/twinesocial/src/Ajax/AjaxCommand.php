<?php

namespace Drupal\twinesocial\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class AjaxCommand.
 */
class AjaxCommand implements CommandInterface {

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'findAccount',
      'message' => 'My Awesome Message',
    ];
  }

}
