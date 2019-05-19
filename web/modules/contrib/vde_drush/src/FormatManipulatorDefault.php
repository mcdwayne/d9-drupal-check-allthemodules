<?php

namespace Drupal\vde_drush;

/**
 * Implements default format manipulator.
 */
class FormatManipulatorDefault implements FormatManipulatorInterface {

  /**
   * Exctracts header from rendered view chunk.
   *
   * @param string $content
   *   Content varible from where headers should be extracted.
   */
  protected function extractFooter(&$content) {
    // No default implementation here.
  }

  /**
   * Exctracts header from rendered view chunk.
   *
   * @param string $content
   *   Content varible from where headers should be extracted.
   */
  protected function extractHeader(&$content) {
    // No default implementation here.
  }

  /**
   * {@inheritdoc}
   */
  public function handle($output_file, &$content, $current_position, $total_items) {
    // Detect whether the output file exists and if so, do not include
    // the header by default, since we can assume the file already contains
    // it.
    if (file_exists($output_file)) {
      $this->extractHeader($content);
    }

    // If current position is at the end of the data set, extract the footer
    // as well.
    if ($current_position < $total_items) {
      $this->extractFooter($content);
    }

    // Write content to the output file.
    return file_put_contents($output_file, $content, FILE_APPEND);
  }

}
