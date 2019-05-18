<?php

namespace Drupal\rokka\StyleEffects;

use Drupal\crop\Entity\CropType;
use Rokka\Client\Core\StackOperation;

/**
 * Class EffectCropCrop
 *
 * @package Drupal\rokka\StyleEffects
 */
class EffectCropCrop extends EffectRokkaCrop {

  /**
   * {@inheritdoc}
   */
  public static function buildRokkaStackOperation($data) {
    $crop_options = [
      'area' => $data['crop_type'],
    ];

    $crop_type = CropType::load($data['crop_type']);
    $aspect_ratio = $crop_type->getAspectRatio();

    if (!empty($aspect_ratio) && strpos($aspect_ratio, ':') > 0) {
      list($width, $height) = explode(':', $aspect_ratio);

      $crop_options['height'] = $height;
      $crop_options['width'] = $width;
      $crop_options['mode'] = 'ratio';
    }

    return [
      new StackOperation('crop', $crop_options),
    ];
  }
}
