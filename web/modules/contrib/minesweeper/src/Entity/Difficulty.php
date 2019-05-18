<?php

/**
 * @file
 * Contains \Drupal\minesweeper\Entity\Difficulty.
 */

namespace Drupal\minesweeper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\minesweeper\DifficultyInterface;

/**
 * Defines the Difficulty entity.
 *
 * @ConfigEntityType(
 *   id = "difficulty",
 *   label = @Translation("Difficulty"),
 *   config_prefix = "difficulty",
 *   admin_permission = "administer minesweeper difficulty",
 *   handlers = {
 *     "list_builder" = "Drupal\minesweeper\DifficultyListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 * )
 */
class Difficulty extends ConfigEntityBase implements DifficultyInterface {

  /**
   * The Difficulty ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Difficulty label.
   *
   * @var string
   */
  public $label;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.

  /**
   * The Difficulty weight.
   *
   * @var integer
   */
  public $weight;

  /**
   * The Difficulty width.
   *
   * @var integer
   */
  protected $board_width;

  /**
   * The Difficulty height.
   *
   * @var integer
   */
  protected $board_height;

  /**
   * The Difficulty mines.
   *
   * @var integer
   */
  protected $mines;

  /**
   * {@inheritdoc}
   */
  public function getBoardWidth() {
    return $this->board_width;
  }

  /**
   * {@inheritdoc}
   */
  public function getBoardHeight() {
    return $this->board_height;
  }

  /**
   * {@inheritdoc}
   */
  public function getMines() {
    return $this->mines;
  }
}
