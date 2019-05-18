<?php

namespace Drupal\eventor_test\EventSubscriber;

use Drupal\eventor\EventListener;
use Drupal\eventor_test\Events\DeathStarWasDestroyed;

/**
 * Class LukeListener.
 */
class LukeListener extends EventListener {

  /**
   * Event handler.
   *
   * @param \Drupal\eventor_test\Events\DeathStarWasDestroyed $event
   *   Event.
   */
  public function whenDeathStarWasDestroyed(DeathStarWasDestroyed $event) {
    $event->lukeIsHappy = TRUE;
  }

}
