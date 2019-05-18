<?php

/**
 * @file
 *
 */

namespace Drupal\retina_images\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\CropImageEffect;
use Drupal\retina_images\RetinaImageEffectTrait;

/**
 * Class RetinaCropImageEffect
 * @package Drupal\retina_images\Plugin\ImageEffect
 */
class RetinaCropImageEffect extends CropImageEffect {
  use RetinaImageEffectTrait;

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    list($x, $y) = explode('-', $this->configuration['anchor']);
    $x = image_filter_keyword($x, $image->getWidth(), $this->configuration['width']);
    $y = image_filter_keyword($y, $image->getHeight(), $this->configuration['height']);
    if (!$image->crop($x, $y, $this->multiplyDimension($this->configuration['width']), $this->multiplyDimension($this->configuration['height']))) {
      $this->logger->error('Image crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'retina_images_image_crop_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }


}
