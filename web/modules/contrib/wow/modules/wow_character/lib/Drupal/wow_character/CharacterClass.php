<?php

/**
 * @file
 * Definition of Drupal\wow_character\CharacterClass.
 */

namespace Drupal\wow_character;

/**
 * Defines the character class.
 */
class CharacterClass extends \Entity {

  /**
   * The character class ID.
   *
   * @var integer
   */
  public $id;

  /**
   * The character class bitmask.
   *
   * @var integer
   */
  public $mask;

  /**
   * The character class power type: focus, mana, energy, rage, runic-power.
   *
   * @var string
   */
  public $powerType;

}
