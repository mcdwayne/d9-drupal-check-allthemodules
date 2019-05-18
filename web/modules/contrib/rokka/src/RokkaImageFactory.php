<?php

namespace Drupal\rokka;

use Drupal\Core\Image\Image;
use Drupal\Core\Image\ImageFactory;

/**
 * Alter the image factory service.
 */
class RokkaImageFactory extends ImageFactory {

  /**
   * {@inheritdoc}
   */
  public function get($source = NULL, $toolkit_id = NULL) {
    $toolkit_id = $toolkit_id ?: $this->toolkitId;

    // Dynamically exchange the GDToolkit with the rokka toolkit to avoid slow getimagesize calls.
    if (!empty($source) && strpos($source, 'rokka://') === 0) {
      $toolkit_id = 'rokka';
    }

    return new Image($this->toolkitManager->createInstance($toolkit_id), $source);
  }
}