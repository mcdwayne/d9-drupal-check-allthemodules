<?php

/**
 * @file
 *
 */

namespace Drupal\retina_images\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Drupal\retina_images\RetinaImageEffectTrait;

/**
 * Class RetinaScaleImageEffect
 * @package Drupal\retina_images\Plugin\ImageEffect
 */
class RetinaResizeImageEffect extends ResizeImageEffect {
  use RetinaImageEffectTrait;

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!$image->resize($this->multiplyDimension($this->configuration['width']), $this->multiplyDimension($this->configuration['height']))) {
      $this->logger->error('Image resize failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
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
