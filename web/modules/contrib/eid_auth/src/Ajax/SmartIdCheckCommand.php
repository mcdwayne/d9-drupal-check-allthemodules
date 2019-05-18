<?php

namespace Drupal\eid_auth\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Url;

/**
 * Class SmartIdCheckCommand.
 *
 * @package Drupal\eid_auth\Ajax
 */
class SmartIdCheckCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $path = Url::fromRoute('eid_auth.ajax_smart_id_check');

    return [
      'command' => 'auth_status_check_command',
      'path' => $path->toString(),
    ];
  }

}
