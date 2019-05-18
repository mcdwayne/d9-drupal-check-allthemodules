<?php

namespace Drupal\entity_tools;

use Drupal\Core\Image\Image;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Class FileTools.
 *
 * Utilities for images and files.
 *
 * @package Drupal\entity_tools
 */
class FileTools {

  /**
   * Get a File URI from a File id.
   *
   * @param int $file_id
   *   File id.
   *
   * @return null|string
   *   File uri.
   */
  public static function getUri($file_id) {
    $result = NULL;
    $file = File::load($file_id);
    if ($file instanceof File) {
      $result = $file->getFileUri();
    }
    return $result;
  }

  /**
   * Returns the path of a module.
   *
   * @param string $name
   *   Module name.
   *
   * @return string
   *   Module path.
   */
  public static function getModulePath($name) {
    $module_handler = \Drupal::service('module_handler');
    $result = $module_handler->getModule($name)->getPath();
    return $result;
  }

  /**
   * Returns image validation errors from a File.
   *
   * @param \Drupal\file\FileInterface $file
   *   File instance.
   *
   * @return array
   *   Validations errors, validation passes if empty.
   */
  public static function validateImage(FileInterface $file) {
    $errors = [];
    $image_factory = \Drupal::service('image.factory');
    $image = $image_factory->get($file->getFileUri());
    if ($image instanceof Image && !$image->isValid()) {
      $supported_extensions = $image_factory->getSupportedExtensions();
      $errors[] = t('Image type not supported. Allowed types: %types', ['%types' => implode(' ', $supported_extensions)]);
    }
    return $errors;
  }

  /**
   * Returns a styled image url.
   *
   * @param string $uri
   *   Image Uri.
   * @param string $style
   *   Image style.
   *
   * @return string
   *   Styled image Url.
   */
  public static function getStyledImageUrl($uri, $style = 'thumbnail') {
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style);
    $result = $style->buildUrl($uri);
    // Equivalent to
    // $result = ImageStyle::load($style)->buildUrl($uri);
    return $result;
  }

  /**
   * Builds a render array for an image style.
   *
   * @param string $uri
   *   Image Uri.
   * @param string $style
   *   Image style.
   * @param array $params
   *   Optional parameters.
   *
   * @return array
   *   Styled image render array.
   */
  public static function imageStyleDisplay($uri, $style = 'thumbnail', array $params = []) {
    $build = [];
    if (!empty($uri)) {
      $build = [
        '#theme' => 'image_style',
        '#style_name' => $style,
        '#uri' => $uri,
      ];
    }
    return $build;
  }

}
