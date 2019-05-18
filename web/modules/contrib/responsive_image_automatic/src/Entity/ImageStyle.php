<?php

/**
 * @file
 * Contains \Drupal\responsive_image_automatic\Entity\ImageStyle.
 */

namespace Drupal\responsive_image_automatic\Entity;

use Drupal\image\Entity\ImageStyle as ImageStyleOriginal;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Drupal\responsive_image_automatic\CalculationsUtility;

/**
 * {@inheritdoc}
 */
class ImageStyle extends ImageStyleOriginal {

  /**
   * The minimum size of a device for a lower bound on image size generation.
   */
  const AUTOMATIC_MINIMUM_DEVICE_WIDTH = 320;

  /**
   * The increments in which images should be created to preserve bandwidth.
   */
  const AUTOMATIC_WIDTH_CHANGE_INCREMENTS = 400;

  /**
   * {@inheritdoc}
   */
  public function createDerivative($original_uri, $derivative_uri) {

    // Ensure the parent behaviour of createDerivative is retained.
    $full_size_derivative = $this->writeDerivative($original_uri, $derivative_uri);
    if (!$full_size_derivative) {
      return FALSE;
    }

    $automatic_derivatives = $this->getAutomaticDerivativeUris($derivative_uri);

    // If There are no automatic derivatives fall back to the default behaviour.
    if (empty($automatic_derivatives)) {
      return $full_size_derivative;
    }

    // Create smaller automatic derivative in the same directory with the
    // reduced size appended to the filename.
    $effect_width = $this->getResizeEffectWidth();
    foreach ($automatic_derivatives as $reduced_width => $filename) {
      $size_ratio = $reduced_width / $effect_width;
      $this->changeResizeEffectSizesByRatio($size_ratio);
      $this->writeDerivative($original_uri, $filename);
      $this->resetResizeEffectConfiguration();
    }

    return $full_size_derivative;
  }

  /**
   * Get the automatic derivatives.
   *
   * @param $original_derivative_uri
   *   The URI of the created derivative URI.
   *
   * @return array
   *   An array of automatic derivatives.
   */
  public function getAutomaticDerivativeUris($original_derivative_uri) {
    $derivatives = [];

    $resize_effect = $this->getResizeEffect();
    if (empty($resize_effect)) {
      return $derivatives;
    }

    $original_size = $this->getResizeEffectWidth();
    if (empty($resize_effect) || empty($original_size) || $original_size < static::AUTOMATIC_MINIMUM_DEVICE_WIDTH + static::AUTOMATIC_WIDTH_CHANGE_INCREMENTS) {
      return $derivatives;
    }

    $automatic_sizes = $this->getAutomaticDerivativeSizes($original_size);
    $original_derivative_dimensions = $this->getDimensions($original_derivative_uri);

    $file_system = $this->getFileSystem();

    $basename = $file_system->basename($original_derivative_uri);
    $pos = strrpos($basename, '.');
    $filename = substr($basename, 0, $pos);
    $extension = substr($basename, $pos);
    $directory = $file_system->dirname($original_derivative_uri);

    foreach ($automatic_sizes as $size) {
      // Don't create derivatives which are equal or bigger in size.
      if ($size >= $original_derivative_dimensions['width']) {
        continue;
      }
      $derivatives[$size] = $directory . DIRECTORY_SEPARATOR . $filename . '_' . $size . $extension;
    }
    return $derivatives;
  }

  /**
   * Get the effect applied to the Image style responsible for resizing.
   *
   * @return bool
   */
  protected function getResizeEffect() {
    if (!isset($this->automaticResizeEffect)) {
      foreach ($this->getEffects()->getIterator() as $effect) {
        if ($effect instanceof ResizeImageEffect) {
          $this->automaticResizeEffect = $effect;
          return $this->automaticResizeEffect;
        }
      }
      $this->automaticResizeEffect = FALSE;
    }
    return $this->automaticResizeEffect;
  }

  /**
   * Get the width of the resize effect on this style.
   *
   * @return int
   *   The width of the effect.
   */
  protected function getResizeEffectWidth() {
    return $this->getResizeEffect()->getConfiguration()['data']['width'];
  }

  /**
   * Change the dimensions of the resize effect by a given ratio.
   *
   * @param float $ratio
   *   The ratio to adjust the effect for.
   */
  protected function changeResizeEffectSizesByRatio($ratio) {
    $resize_effect = $this->getResizeEffect();
    $configuration = $resize_effect->getConfiguration();
    $this->originalResizeEffectConfiguration = $configuration;
    if (!empty($configuration['data']['width'])) {
      $configuration['data']['width'] *= $ratio;
    }
    if (!empty($configuration['data']['height'])) {
      $configuration['data']['height'] *= $ratio;
    }
    $resize_effect->setConfiguration($configuration);
  }

  /**
   * Reset the configuration of the resize effect.
   */
  protected function resetResizeEffectConfiguration() {
    $this->getResizeEffect()->setConfiguration($this->originalResizeEffectConfiguration);
  }

  /**
   * Get the widths of the automatic derivatives appropriate for a given size.
   *
   * @param int $image_size
   *   The original image size.
   *
   * @return array
   *   An array of widths.
   */
  protected function getAutomaticDerivativeSizes($image_size) {
    $total_sizes = floor(($image_size - static::AUTOMATIC_MINIMUM_DEVICE_WIDTH) / static::AUTOMATIC_WIDTH_CHANGE_INCREMENTS);
    $sizes = [];
    foreach (range(1, $total_sizes) as $i) {
      $sizes[] = $image_size - ($i * static::AUTOMATIC_WIDTH_CHANGE_INCREMENTS);
    }
    return $sizes;
  }

  /**
   * Get the file system service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   The file system.
   */
  public function getFileSystem() {
    return \Drupal::service('file_system');
  }

  /**
   * Get the dimensions of an image on the file system.
   *
   * @param $uri
   *   The URI the poll.
   *
   * @return array
   *   An array of dimensions for the given URI.
   */
  public function getDimensions($uri) {
    list($width, $height) = getimagesize($uri);
    return [
      'width' => $width,
      'height' => $height,
    ];
  }

  /**
   * The parent createDerivative method which writes to the file system.
   */
  public function writeDerivative($original_uri, $derivative_uri) {
    return parent::createDerivative($original_uri, $derivative_uri);
  }

}
