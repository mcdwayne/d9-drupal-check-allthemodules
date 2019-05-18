<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectFocalPointScaleAndCrop implements InterfaceEffectImage {

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
      // https://rokka.io/documentation/references/operations.html
      // auto will crop the image centering the crop box around the defined Subject Area,
      // if any exist, then around a face detection box , if any exist. If both are not defined defined,
      // the crop operation will fallback to center_center.
      new StackOperation('crop', array_merge($options, ['anchor' => 'auto'])),
    ];
  }

}
