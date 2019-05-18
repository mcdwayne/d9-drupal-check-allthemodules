<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectImageResize implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {

    $options = [
      'upscale' => (isset($data['upscale'])) ? (boolean) $data['upscale'] : FALSE,
      'height' => ImageStyleHelper::operationNormalizeSize($data['height']),
      'width' => ImageStyleHelper::operationNormalizeSize($data['width']),
    ];
    return [new StackOperation('resize', $options)];
  }

}
