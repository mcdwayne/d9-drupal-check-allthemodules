<?php

namespace Drupal\rokka\StyleEffects;

/**
 *
 */
class EffectImageCrop extends EffectRokkaCrop {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $data = array_merge($data, [
      'background_color' => '#000000',
      'background_opacity' => 100,
    ]);

    return parent::buildRokkaStackOperation($data);
  }

}
