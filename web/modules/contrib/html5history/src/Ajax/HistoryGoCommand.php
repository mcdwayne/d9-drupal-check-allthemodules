<?php

namespace Drupal\html5history\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An ajax command to send the browser history back one frame.
 */
class HistoryGoCommand implements CommandInterface {

  /**
   * The relative frame number to go to.
   *
   * @var int
   */
  protected $cursor;

  /**
   * Creates a history go object.
   *
   * @param int $cursor
   *   The relative frame number to go to. '0' is the current frame, positive
   *   numbers move the history forward. Negative numbers move the history
   *   backwards. Strings are not supported.
   */
  public function __construct($cursor) {
    if (!is_numeric($cursor)) {
      throw new \InvalidArgumentException('The HTML 5 spec requires that the parameter to "go" be an integer.');
    }

    $this->cursor = $cursor;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'history_back',
    ];
  }

}
