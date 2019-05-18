<?php

namespace Drupal\commander_test\Commands;

use Drupal\commander\Contracts\CommandInterface;

/**
 * Class RegisterUser.
 */
class RegisterUser implements CommandInterface {

  /**
   * Registered status.
   *
   * @var bool
   */
  public $registered;

  /**
   * RegisterUser constructor.
   *
   * @param bool $registered
   *   Registered status.
   */
  public function __construct($registered) {
    $this->registered = $registered;
  }

  /**
   * Handler plugin ID.
   *
   * @return string
   *   Plugin ID.
   */
  public function handlerPluginId() {
    return 'register_user_handler';
  }

}
