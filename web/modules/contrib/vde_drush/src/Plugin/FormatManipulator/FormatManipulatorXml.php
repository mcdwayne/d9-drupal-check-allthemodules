<?php

namespace Drupal\vde_drush\Plugin\FormatManipulator;

use Drupal\vde_drush\FormatManipulatorDefault;

/**
 * Implements xml format handler.
 *
 * @FormatManipulator(
 *   id="xml"
 * )
 */
class FormatManipulatorXml extends FormatManipulatorDefault {

  /**
   * {@inheritdoc}
   */
  protected function extractHeader(&$content) {
    // Remove xml header.
    $content = preg_replace('(<\?xml.*?\?>)', '', $content);

    // Remove response root.
    $content = str_replace('<response>', '', $content);
  }

  /**
   * {@inheritdoc}
   */
  protected function extractFooter(&$content) {
    // Remove response header.
    $content = str_replace('</response>', '', $content);
  }

}
