<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\rokka\ImageStyleHelper;
use Rokka\Client\Core\StackOperation;

/**
 *
 */
class EffectImageEffectsSetCanvas implements InterfaceEffectImage {

  /**
   *
   */
  public static function buildRokkaStackOperation($data) {
    $options = [
      'mode' => 'foreground',
      'secondary_color' => substr(str_replace('#', '', $data['canvas_color']), 0, 6),
    ];

    // width and height are optional.
    if(!empty($data['exact']['width'])) {
      $options['width'] = ImageStyleHelper::operationNormalizeSize($data['exact']['width']);
    }
    if(!empty($data['exact']['height'])) {
      $options['height'] = ImageStyleHelper::operationNormalizeSize($data['exact']['height']);
    }

    return [new StackOperation('composition', $options)];
  }

}
