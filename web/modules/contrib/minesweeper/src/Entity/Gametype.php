<?php

/**
 * @file
 * Contains \Drupal\minesweeper\Entity\Gametype.
 */

namespace Drupal\minesweeper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\minesweeper\GametypeInterface;

/**
 * Defines the Example entity.
 *
 * @ConfigEntityType(
 *   id = "gametype",
 *   label = @Translation("Game type"),
 *   config_prefix = "gametype",
 *   admin_permission = "administer minesweeper gametype",
 *   handlers = {
 *     "list_builder" = "Drupal\minesweeper\GametypeListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 * )
 */
class Gametype extends ConfigEntityBase implements GametypeInterface {

  /**
   * The Gametype ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Gametype label.
   *
   * @var string
   */
  public $label;

  // Your specific configuration property get/set methods go here,
  // implementing the interface.

  /**
   * The Gametype weight.
   *
   * @var integer
   */
  public $weight;

  /**
   * The Gametype description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Gametype multiplayer.
   *
   * @var boolean
   */
  protected $multiplayer;

  /**
   * The Gametype difficulty..
   *
   * @var array
   */
  protected $difficulty;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiplayer() {
    return $this->multiplayer;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedDifficulties() {
    return $this->difficulty;
  }
}

