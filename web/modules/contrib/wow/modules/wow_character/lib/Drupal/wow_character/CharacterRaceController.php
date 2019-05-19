<?php

/**
 * @file
 * Definition of WoW\character\CharacterRaceController.
 */

namespace Drupal\wow_character;

use Drupal\wow\Data\DataServiceController;

/**
 * Controller class for character races.
 *
 * This extends the DataServiceController class, adding required special
 * handling for character race objects.
 */
class CharacterRaceController extends DataServiceController {

  public function create(array $values = array()) {
    $entity = parent::create($values);
    $entity->wow_character_race= array(
      $values['language'] => array(0 => array('name' => $values['name']))
    );
    return $entity;
  }

  public function remotePath() {
    return 'data/character/races';
  }

}
