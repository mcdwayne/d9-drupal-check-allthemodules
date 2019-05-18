<?php

namespace Drupal\fancy_login\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines the popup ajax command.
 */
class FancyLoginClosePopupCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'fancyLoginClosePopup',
    ];
  }

}
