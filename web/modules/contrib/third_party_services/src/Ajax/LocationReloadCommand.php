<?php

namespace Drupal\third_party_services\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Trigger "window.location.reload()" within the frontend.
 */
class LocationReloadCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'locationReload',
    ];
  }

}
