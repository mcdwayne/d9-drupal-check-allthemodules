<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectImageRotate implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {

    $useTransparency = empty($data['bgcolor']);
    // If no background color has been defined, use white with 0 opacity.
    $backgroundColor = $useTransparency ? 'FFFFFF' : ImageStyleHelper::operationNormalizeColor($data['bgcolor']);

    $options = [
      'angle' => ImageStyleHelper::operationNormalizeAngle($data['degrees']),
      'background_opacity' => $useTransparency ? 0 : 100,
      'background_color' => $backgroundColor,
    ];

    return [new StackOperation('rotate', $options)];
  }

}
