<?php

namespace Drupal\vde_drush\Plugin\FormatManipulator;

use Drupal\vde_drush\FormatManipulatorDefault;

/**
 * Implements csv format handler.
 *
 * @FormatManipulator(
 *   id="csv"
 * )
 */
class FormatManipulatorCsv extends FormatManipulatorDefault {

  /**
   * {@inheritdoc}
   */
  protected function extractHeader(&$content) {
    $content = preg_replace('/^[^\n]+/', '', $content);
  }

}
