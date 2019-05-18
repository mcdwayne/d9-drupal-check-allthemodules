<?php

namespace Drupal\block_ajax\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class RefreshBlockAjaxCommand implements CommandInterface {

  /**
   * @var string
   */
  protected $selector;

  public function __construct($selector) {
    $this->selector = $selector;
  }

  public function render() {
    return [
      'command' => 'refreshBlockAjaxCommand',
      'selector' => $this->selector,
    ];
  }
}
