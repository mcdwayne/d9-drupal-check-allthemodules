<?php

namespace Drupal\sl_match_moments\Entity;

use Drupal\eck\Entity\EckEntity;
use Drupal\eck\EckEntityInterface;

class SlMatchMomentsEntity extends EckEntity implements EckEntityInterface {
  function label() {
    
    if (!is_null($this->field_sl_match_moments_player->entity)) {
      $label = $this->field_sl_match_moments_player->entity->label();
      return $label;
    }
    else if (!empty($this->field_sl_match_moments_des)) {
      return $this->field_sl_match_moments_des->value;
    }
  }

}