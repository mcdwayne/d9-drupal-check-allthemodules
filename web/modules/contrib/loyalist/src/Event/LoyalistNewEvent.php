<?php

namespace Drupal\loyalist\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class LoyalistNewEvent.
 *
 * @ingroup loyalist
 */
class LoyalistNewEvent extends Event {

  /**
   * Event name.
   *
   * @var string
   */
  const EVENT_NAME = 'loyalist.new';

}
