<?php

namespace Drupal\sl_rosters\Entity;

use Drupal\eck\Entity\EckEntity;
use Drupal\eck\EckEntityInterface;

class SlRostersEntity extends EckEntity implements EckEntityInterface {
  function label() {
    if (!empty($this->field_sl_roster_player_name)) {
      return $this->field_sl_roster_player_name->value;
    }
    else if (!is_null($this->field_sl_roster_player->entity)) {
      return $this->field_sl_roster_player->entity->label();
    }
  }
}

