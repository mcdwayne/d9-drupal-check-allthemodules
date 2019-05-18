<?php

namespace Drupal\flow_player_wysiwyg\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class FlowPlayerCommand.
 *
 * @package Drupal\flow_player_wysiwyg\Ajax
 */
class FlowPlayerCommand implements CommandInterface {

  /**
   * Constructs a EditorDialogSave object.
   *
   * @param string $type
   *   The values that should be passed to the form constructor in Drupal.
   */
  public function __construct($type) {
    $this->type = $type;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'flowPlayerCommand',
      'type' => $this->type,
    ];
  }

}
