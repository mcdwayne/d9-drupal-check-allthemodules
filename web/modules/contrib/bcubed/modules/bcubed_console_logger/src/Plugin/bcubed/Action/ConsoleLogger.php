<?php

namespace Drupal\bcubed_console_logger\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;

/**
 * Logs connected events to console.
 *
 * @Action(
 *   id = "console_logger",
 *   label = @Translation("JS Console Logger"),
 *   description = @Translation("Logs connected events to JS console")
 * )
 */
class ConsoleLogger extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_console_logger/logger';
  }

}
