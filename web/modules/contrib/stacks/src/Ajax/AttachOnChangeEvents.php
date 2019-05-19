<?php
namespace Drupal\stacks\Ajax;
use Drupal\Core\Ajax\CommandInterface;

class AttachOnChangeEvents implements CommandInterface {
  protected $selector;

  // Constructs a CancelWidget object.
  public function __construct($selector) {
    $this->selector = $selector;
  }

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return [
      'command' => 'attachOnChangeEvents',
      'selector' => $this->selector,
    ];
  }
}
