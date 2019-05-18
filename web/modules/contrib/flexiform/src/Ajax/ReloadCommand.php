<?php

namespace Drupal\flexiform\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides the reload AJAX command.
 */
class ReloadCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'reload',
    ];
  }

}
