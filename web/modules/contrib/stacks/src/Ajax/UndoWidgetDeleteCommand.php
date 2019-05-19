<?php
namespace Drupal\stacks\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class UndoWidgetDeleteCommand.
 * @package Drupal\stacks\Ajax
 */
class UndoWidgetDeleteCommand implements CommandInterface {
  protected $selector;
  protected $value;

  /**
   * Constructs a CancelWidget object.
   * @param $selector
   * @param $value
   */
  public function __construct($selector, $value) {
    $this->selector = $selector;
    $this->value = $value;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'undoWidgetDelete',
      'selector' => $this->selector,
      'value' => $this->value,
    ];
  }

}
