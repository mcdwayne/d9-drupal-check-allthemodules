<?php

namespace Drupal\simple_modal_entity_form\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an Ajax command for scrolling to the top of an element.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.modalEntityFormScrollTop.
 */
class ModalEntityFormScrollTopCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'modalEntityFormScrollTop',
    ];
  }

}
