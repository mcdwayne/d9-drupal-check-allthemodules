<?php

namespace Drupal\entity_sanitizer_image_fallback\StreamWrapper;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

trait GeneratorStreamTrait {

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {

    // If this is not an image we just let the normal Private Stream serve it.
    if (!$this->isSupportedFileType()) {
      return parent::getExternalUrl();
    }

    // Checks whether the source image for the requested image exists on disk.
    if (is_file($this->getOrginalPath())) {
      return parent::getExternalUrl();
    }

    // We default to 512x512.
    $width = 512;
    $height = 512;

    // If we can figure out the known image style we can specify custom dimensions.
    $style = $this->getImageStyle();
    if (!is_null($style)) {
      $is = ImageStyle::load($style);

      foreach ($is->getEffects() as $effect) {
        $configuration = $effect->getConfiguration();

        // If this image effect sets a width and height we use that.
        // We assume the last image effect determines the final dimensions.
        if (isset($configuration['data'])) {
          if (isset($configuration['data']['width'])) {
            $width = $configuration['data']['width'];
          }
          if (isset($configuration['data']['height'])) {
            $height = $configuration['data']['height'];
          }
        }
      }
    }

    return Url::fromRoute('entity_sanitizer_image_fallback.generator', ['width' => $width, 'height' => $height, 'filetype' => $this->getFileType()], ['absolute' => TRUE, 'path_processing' => FALSE])->toString();
  }

  /**
   * Fetches the filetype from the image's path.
   *
   * @return string
   *   A file type string (e.g. jpg, png, pdf).
   */
  protected function getFileType() {
    $path_components = explode(".", $this->getPath());
    return strtolower(end($path_components));
  }

  /**
   * Checks whether this file type is of an image that we handle.
   *
   * @return bool
   *   Whether we support this filetype.
   */
  protected function isSupportedFileType() {
    return in_array($this->getFileType(), ['png', 'jpg', 'jpeg', 'gif']);
  }

  /**
   * Normalises the target path.
   *
   * @return string
   *   The normalised path of the requested file.
   */
  protected function getPath() {
    return str_replace('\\', '/', $this->getTarget());
  }

  /**
   * Returns the image style for this request.
   *
   * @return string|NULL
   *   The image style for this request or NULL if this is the original image.
   */
  protected function getImageStyle() {
    $components = explode("/", $this->getTarget());
    if (!empty($components) && $components[0] === "styles") {
      return $components[1];
    }

    return NULL;
  }

  /**
   * Finds the non-image-style path for our stream target.
   *
   * @return string
   *   A non-image-style path or the original target if this wasn't an image style request.
   *
   * @see ImageStyle::buildUri
   */
  protected function getOrginalPath() {
    $components = explode("/", $this->getTarget());
    if (!empty($components) && $components[0] === "styles") {
      $scheme = $components[2];
      // Removes styles/<style>/<scheme> from the components.
      $components = array_slice($components, 3);

      return $this->getLocalPath($scheme . "://" . join("/", $components));
    }

    return $this->realpath();
  }
}