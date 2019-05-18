<?php

/**
 * @file
 *
 */

namespace Drupal\retina_images\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\ScaleAndCropImageEffect;
use Drupal\retina_images\RetinaImageEffectTrait;

/**
 * Class RetinaScaleAndCropImageEffect
 * @package Drupal\retina_images\Plugin\ImageEffect
 */
class RetinaScaleAndCropImageEffect extends ScaleAndCropImageEffect {
  use RetinaImageEffectTrait;

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->scaleAndCrop($this->multiplyDimension($this->configuration['width']), $this->multiplyDimension($this->configuration['height']))) {
      $this->logger->error('Image scale and crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'retina_images_image_resize_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }


}
