<?php

namespace Drupal\stacks\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class CancelWidgetCommand.
 * @package Drupal\stacks\Ajax
 */
class CancelWidgetCommand implements CommandInterface {
  protected $selector;

  /**
   * Constructs a CancelWidget object.
   * @param $selector
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'cancelWidget',
      'selector' => $this->selector,
    ];
  }

}
