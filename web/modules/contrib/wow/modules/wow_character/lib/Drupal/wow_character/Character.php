<?php

/**
 * @file
 * Definition of Drupal\wow_character\Character.
 */

namespace Drupal\wow_character;

use Drupal\wow\Entity\Remote;

/**
 * Defines the wow_character entity class.
 */
class Character extends Remote {

  /**
   * The character ID.
   *
   * @var integer
   */
  public $cid;

  /**
   * The character owner's user ID.
   *
   * @var integer
   */
  public $uid;

  /**
   * The character realm (slug).
   *
   * @var string
   */
  public $realm;

  /**
   * The character name.
   *
   * @var string
   */
  public $name;

  /**
   * The character level.
   *
   * @var integer
   */
  public $level;

  /**
   * Whether the character is active(1) or blocked(0).
   *
   * @var integer
   */
  public $status;

  /**
   * Whether the character is main(1) or alt(0).
   *
   * @var integer
   */
  public $isMain;

  /**
   * The character thumbnail.
   *
   * @var string
   */
  public $thumbnail;

  /**
   * The character race.
   *
   * @var integer
   */
  public $race;

  /**
   * The character achievement points.
   *
   * @var integer
   */
  public $achievementPoints;

  /**
   * The character gender.
   *
   * @var integer
   */
  public $gender;

  /**
   * The character class.
   *
   * @var integer
   */
  public $class;

}
