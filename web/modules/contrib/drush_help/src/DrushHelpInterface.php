<?php

namespace Drupal\drush_help;

/**
 * Interface DrushHelpInterface.
 *
 * @package Drupal\drush_help
 */
interface DrushHelpInterface {

  /**
   * Return the drush command help html.
   *
   * @return string
   *   The drush command help html.
   */
  public function getDrushCommandsHelp($drush_commands);

}
