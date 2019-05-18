<?php


/**
 * @file
 * Contains \Drupal\minesweeper\DifficultyInterface.
 */

namespace Drupal\minesweeper;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining my configurable data object.
 */
interface DifficultyInterface extends ConfigEntityInterface {
  public function getBoardWidth();
  public function getBoardHeight();
  public function getMines();
}
