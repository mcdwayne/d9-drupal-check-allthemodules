<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectRokkaBlur implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $options = [
      'radius' => ImageStyleHelper::operationNormalizeSize($data['radius']),
      'sigma' => ImageStyleHelper::operationNormalizeSize($data['sigma']),
    ];

    return [
      new StackOperation('blur', $options),
    ];
  }

}
