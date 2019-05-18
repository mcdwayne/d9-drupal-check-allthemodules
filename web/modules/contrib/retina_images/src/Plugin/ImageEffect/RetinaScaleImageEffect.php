<?php

/**
 * @file
 *
 */

namespace Drupal\retina_images\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\ScaleImageEffect;
use Drupal\retina_images\RetinaImageEffectTrait;

/**
 * Class RetinaScaleImageEffect
 * @package Drupal\retina_images\Plugin\ImageEffect
 */
class RetinaScaleImageEffect extends ScaleImageEffect {
  use RetinaImageEffectTrait;

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->scale($this->multiplyDimension($this->configuration['width']), $this->multiplyDimension($this->configuration['height']), $this->configuration['upscale'])) {
      $this->logger->error('Image scale failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'retina_images_image_scale_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }


}
