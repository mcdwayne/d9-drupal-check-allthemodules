<?php

namespace Drupal\html5history\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An ajax command to send the browser history forward one frame.
 */
class HistoryForwardCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'history_forward',
    ];
  }

}
