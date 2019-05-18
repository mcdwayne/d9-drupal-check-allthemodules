<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectImageScaleAndCrop implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $options = [
      'height' => ImageStyleHelper::operationNormalizeSize($data['height']),
      'width' => ImageStyleHelper::operationNormalizeSize($data['width']),
    ];

    return [
      new StackOperation('resize', array_merge($options, ['mode' => 'fill'])),
      new StackOperation('crop', $options),
    ];
  }

}
