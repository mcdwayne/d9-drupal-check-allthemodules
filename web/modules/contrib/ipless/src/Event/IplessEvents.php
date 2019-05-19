<?php

namespace Drupal\ipless\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class event IplessEvent
 *
 * @author Damien LAGUERRE
 */
final class IplessEvents extends Event {

  /**
   * Name of the event fired when a less file is compiled.
   *
   * @Event
   */
  const LESS_FILE_COMPILED = 'ipless.file_compilation';

}
