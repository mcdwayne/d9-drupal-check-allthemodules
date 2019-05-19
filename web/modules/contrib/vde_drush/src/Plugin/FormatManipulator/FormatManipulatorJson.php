<?php

namespace Drupal\vde_drush\Plugin\FormatManipulator;

use Drupal\vde_drush\FormatManipulatorDefault;

/**
 * Implements json format handler.
 *
 * @FormatManipulator(
 *   id="json"
 * )
 */
class FormatManipulatorJson extends FormatManipulatorDefault {

  /**
   * {@inheritdoc}
   */
  protected function extractHeader(&$content) {
    $content = preg_replace('(^\[)', '', $content);
  }

  /**
   * {@inheritdoc}
   */
  protected function extractFooter(&$content) {
    $content = preg_replace('(\]$)', '', $content);

    // Append a comma at the end.
    $content .= ',';
  }

}
