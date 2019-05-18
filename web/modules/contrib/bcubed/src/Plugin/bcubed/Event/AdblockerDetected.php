<?php

namespace Drupal\bcubed\Plugin\bcubed\Event;

use Drupal\bcubed\EventBase;

/**
 * Provides basic adblocker detected event.
 *
 * @Event(
 *   id = "adblocker_detected",
 *   label = @Translation("Adblocker Detected"),
 *   description = @Translation("Basic adblocker detection"),
 *   generated_strings_dictionary = "bcubed"
 * )
 */
class AdblockerDetected extends EventBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed/adblockerdetect';
  }

  /**
   * {@inheritdoc}
   */
  public function getEventName() {
    return 'adblockerDetected';
  }

}
