<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectRokkaCrop implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $crop_options = [
      'height' => ImageStyleHelper::operationNormalizeSize($data['height']),
      'width' => ImageStyleHelper::operationNormalizeSize($data['width']),
      'anchor' => $data['anchor'],
    ];

    $composite_options = array_merge($crop_options, [
      'mode' => 'foreground',
      'secondary_color' => ImageStyleHelper::operationNormalizeColor($data['background_color']),
      'secondary_opacity' => (int) $data['background_opacity'],
    ]);

    return [
      new StackOperation('crop', $crop_options),
      new StackOperation('composition', $composite_options),
    ];
  }

}
