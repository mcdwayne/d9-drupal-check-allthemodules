<?php

namespace Drupal\cleaner\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CleanerRunEvent.
 *
 * @package Drupal\cleaner\Event
 */
class CleanerRunEvent extends Event {

  /**
   * Event name.
   */
  const CLEANER_RUN = 'cleaner.run';

}
