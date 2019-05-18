<?php

namespace Drupal\loyalist\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class LoyalistNewEvent.
 *
 * @ingroup loyalist
 */
class LoyalistVisitEvent extends Event {

  /**
   * Event name.
   *
   * @var string
   */
  const EVENT_NAME = 'loyalist.visit';

}
