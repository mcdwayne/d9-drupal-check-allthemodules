<?php

/**
 * UnitTestDummyHandlerInterrupt.
 */
class UnitTestDummyHandlerInterrupt extends \Drupal\acsf\Event\AcsfEventHandler {

  /**
   * Dummy handler.
   */
  public function handle() {
    $this->event->dispatcher->interrupt();
  }

}
