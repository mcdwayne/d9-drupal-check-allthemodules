<?php

namespace Drupal\geysir\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command that closes the current active dialog.
 *
 * @ingroup ajax
 */
class GeysirReattachBehaviors implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'geysirReattachBehaviors',
    ];
  }

}
