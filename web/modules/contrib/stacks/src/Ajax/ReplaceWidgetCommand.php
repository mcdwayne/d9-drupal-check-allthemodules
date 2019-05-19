<?php

namespace Drupal\stacks\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ReplaceWidgetCommand.
 * @package Drupal\stacks\Ajax
 */
class ReplaceWidgetCommand implements CommandInterface {
  protected $selector;
  protected $data;

  /**
   * Constructs a CancelWidget object.
   * @param $selector
   */
  public function __construct($selector, $data) {
    $this->selector = $selector;
    $this->data = $data;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'replaceWidget',
      'selector' => $this->selector,
      'data' => $this->data,
      'url' => ''
    ];
  }

}
