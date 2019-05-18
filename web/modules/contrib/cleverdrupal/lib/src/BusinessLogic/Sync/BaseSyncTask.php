<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Task;

/**
 *
 */
abstract class BaseSyncTask extends Task {
  /**
   * @var \CleverReach\BusinessLogic\Interfaces\Proxy
   */
  private $proxy;

  /**
   * Gets proxy class instance.
   *
   * @return \CleverReach\BusinessLogic\Proxy
   */
  protected function getProxy() {
    if ($this->proxy === NULL) {
      $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    return $this->proxy;
  }

}
