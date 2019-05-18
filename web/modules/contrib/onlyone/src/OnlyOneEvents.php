<?php

namespace Drupal\onlyone;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class OnlyOneEvents.
 */
class OnlyOneEvents extends Event {

  /**
   * Event related to a change in the content types configuration.
   */
  const CONTENT_TYPES_UPDATED = 'onlyone.content_types_updated';

}
