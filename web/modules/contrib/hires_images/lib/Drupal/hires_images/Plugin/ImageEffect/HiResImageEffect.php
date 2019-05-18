<?php

/**
 * @file
 * Contains \Drupal\hires_images\Plugin\ImageEffect\HiResImageEffect.
 */

namespace Drupal\hires_images\Plugin\ImageEffect;

use Drupal\Core;
use Drupal\image;

/**
 * Resizes an image resource.
 *
 * @image\Annotation\ImageEffect(
 *   id = "image_hires",
 *   label = @Core\Annotation\Translation("Hi-Res (x2)"),
 *   description = @Core\Annotation\Translation("<img> dimensions will be half the dimensions of the styled image.")
 * )
 */
class HiResImageEffect extends image\ImageEffectBase implements image\ImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(Core\Image\ImageInterface $image) {}

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    if (isset($dimensions['width']) && isset($dimensions['height'])) {
      $dimensions['width'] /= 2;
      $dimensions['height'] /= 2;
    }
  }
}
