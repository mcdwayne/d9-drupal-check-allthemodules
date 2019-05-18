<?php

namespace Drupal\ajax_link_change\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class AjaxLinkChangeCommand.
 */
class AjaxLinkChangeCommand implements CommandInterface {

  protected $currentValue;

  /**
   * Constructs an AjaxLinkChangeCommand object.
   *
   * @param mixed $currentValue
   *   The current Value of field.
   */
  public function __construct($currentValue) {
    $this->currentValue = $currentValue;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'AjaxLinkChangeCommand',
      'current_value' => $this->currentValue,
    ];
  }

}
