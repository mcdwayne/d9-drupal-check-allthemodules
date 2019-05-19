<?php

/**
 * @file
 * Definition of BattleGroup.
 */

namespace WoW\Realm\Entity;

use WoW\Core\Entity\Entity;

/**
 * The battlegroups data API provides the list of battlegroups for a region.
 */
class BattleGroup extends Entity {

  /**
   * BattleGroup's ID.
   */
  public $id;

  /**
   * BattleGroup's machine name.
   */
  public $slug;

  /**
   * BattleGroup's name.
   */
  public $name;

}
