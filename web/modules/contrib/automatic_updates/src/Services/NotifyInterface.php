<?php

namespace Drupal\automatic_updates\Services;

/**
 * Interface NotifyInterface.
 */
interface NotifyInterface {

  /**
   * Send notification when PSAs are available.
   */
  public function send();

}
