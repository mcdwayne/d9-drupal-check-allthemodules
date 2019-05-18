<?php

namespace Drupal\rokka\Plugin\ImageToolkit;

use Drupal\system\Plugin\ImageToolkit\GDToolkit;

/**
 * Provides ImageMagick integration toolkit for image manipulation.
 *
 * @ImageToolkit(
 *   id = "rokka",
 *   title = @Translation("Rokka image toolkit")
 * )
 */
class RokkaToolkit extends GDToolkit {

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return ((bool) $this->getMimeType());
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $data = NULL;
    // Use the cached meta data from the rokka metadata table.
    $metadata = \Drupal::service('rokka.service')->loadRokkaMetadataByUri($this->getSource());
    if (!empty($metadata)) {
      $metadata = reset($metadata);

      // Mimic the getimagesize function of php
      $data[0] = $metadata->getWidth();
      $data[1] = $metadata->getHeight();
      $data[2] = $this->extensionToImageType($metadata->getFormat());
    }

    if ($data && in_array($data[2], static::supportedTypes())) {
      $this->setType($data[2]);
      $this->preLoadInfo = $data;
      return TRUE;
    }
    return FALSE;
  }

}
