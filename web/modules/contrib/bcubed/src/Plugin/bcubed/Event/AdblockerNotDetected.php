<?php

namespace Drupal\bcubed\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides basic adblocker detected event.
 *
 * @Event(
 *   id = "adblocker_not_detected",
 *   label = @Translation("Adblocker Not Detected"),
 *   description = @Translation("Basic adblocker detection"),
 *   generated_strings_dictionary = "bcubed"
 * )
 */
class AdblockerNotDetected extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'adblockerNotDetected';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/adblockerdetect';
  }

}
