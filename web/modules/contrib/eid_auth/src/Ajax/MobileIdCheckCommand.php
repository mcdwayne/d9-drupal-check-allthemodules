<?php

namespace Drupal\eid_auth\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Url;

/**
 * Class MobileIdCheckCommand.
 *
 * @package Drupal\eid_auth\Ajax
 */
class MobileIdCheckCommand implements CommandInterface {

  protected $sessionCode;
  protected $personalIdCode;

  /**
   * MobileIdCheckCommand constructor.
   *
   * @param string $session_code
   *   Authentication session code.
   * @param string $personal_id_code
   *   User personal ID code.
   */
  public function __construct($session_code, $personal_id_code) {
    $this->sessionCode = $session_code;
    $this->personalIdCode = $personal_id_code;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $path = Url::fromRoute('eid_auth.ajax_mobile_id_check', [
      'session' => $this->sessionCode,
      'personal_id_code' => $this->personalIdCode,
    ]);

    return [
      'command' => 'auth_status_check_command',
      'path' => $path->toString(),
    ];
  }

}
