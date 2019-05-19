<?php

namespace Drupal\vde_drush;

/**
 * Interface FormatManipulatorInterface.
 *
 * @package Drupal\vde_drush
 */
interface FormatManipulatorInterface {

  /**
   * Handles content manipulation for the alleged target file.
   *
   * @param string $output_file
   *   Filename to output.
   * @param string $content
   *   Content which should be written to file.
   * @param int $current_position
   *   Current batch position.
   * @param int $total_items
   *   Total number of items.
   *
   * @return bool
   *   TRUE in case of success, FALSE otherwise.
   */
  public function handle($output_file, &$content, $current_position, $total_items);

}
