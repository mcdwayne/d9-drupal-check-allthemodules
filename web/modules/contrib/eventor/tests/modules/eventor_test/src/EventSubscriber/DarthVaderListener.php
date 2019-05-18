<?php

namespace Drupal\eventor_test\EventSubscriber;

use Drupal\eventor\EventListener;
use Drupal\eventor_test\Events\DeathStarWasDestroyed;

/**
 * Class DarthVaderListener.
 */
class DarthVaderListener extends EventListener {

  /**
   * Event handler.
   *
   * @param \Drupal\eventor_test\Events\DeathStarWasDestroyed $event
   *   Event.
   */
  public function whenDeathStarWasDestroyed(DeathStarWasDestroyed $event) {
    $event->darthVaderIsHappy = FALSE;
  }

}
