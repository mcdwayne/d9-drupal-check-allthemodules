<?php


/**
 * @file
 * Contains \Drupal\minesweeper\GametypeInterface.
 */

namespace Drupal\minesweeper;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining my configurable data object.
 */
interface GametypeInterface extends ConfigEntityInterface {
  public function getDescription();
  public function getMultiplayer();
  public function getAllowedDifficulties();
}
