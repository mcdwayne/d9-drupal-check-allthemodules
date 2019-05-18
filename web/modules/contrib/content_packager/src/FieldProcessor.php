<?php

namespace Drupal\content_packager;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\image\Entity\ImageStyle;

/**
 * A helper that turns known fields into filenames.
 *
 * @package Drupal\content_packager
 */
class FieldProcessor {

  /**
   * Convert file field items into file URIs to be packaged.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $fieldItemList
   *   File field items to turn into URLs.
   *
   * @return array
   *   Return an array of file uri.
   */
  public static function processFileField(FileFieldItemList $fieldItemList) {
    $uris = [];

    /** @var \Drupal\file\Plugin\Field\FieldType\FileItem $item */
    foreach ($fieldItemList as $item) {
      $value = $item->getValue();
      $file = File::load($value['target_id']);
      $uris[] = $file->getFileUri();
    }

    return $uris;
  }

  /**
   * Convert image field items into file URIs to be packaged.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $fieldItemList
   *   File field items to turn into URLs.
   * @param array $options
   *   Options, used to refine/filter behavior during packaging.
   *   See EntityProcessor.
   *
   * @return array
   *   Return an array of file uri.
   */
  public static function processImageField(FileFieldItemList $fieldItemList, array $options) {
    $uris = [];

    $styles_to_pack = $options['image_styles'];
    $include_original = \Drupal::config('content_packager.settings')->get('include_orig_image');

    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    foreach ($fieldItemList as $item) {
      $value = $item->getValue();

      $file = File::load($value['target_id']);
      $primary_uri = $file->getFileUri();
      if ($include_original) {
        $uris[] = $primary_uri;
      }

      $styles = ImageStyle::loadMultiple($styles_to_pack);

      /** @var \Drupal\image\Entity\ImageStyle $style */
      foreach ($styles as $style) {
        $style_uri = self::generateImageStyle($primary_uri, $style);
        if ($style_uri) {
          $uris[] = $style_uri;
        }
      }
    }
    return $uris;
  }

  /**
   * Generate (if necessary) an image style.
   *
   * Image style gets flushed when appropriate by image module, so if the file
   * exists we don't need to create it again.
   *
   * @param string $image_uri
   *   The image URI.
   * @param \Drupal\image\Entity\ImageStyle $style
   *   The style we want to derive for $image_uri.
   *
   * @return bool|string
   *   Returns the URI to the derived style or FALSE if failure.
   *
   * @see ImageStyle::postSave()
   */
  private static function generateImageStyle($image_uri, ImageStyle $style) {
    $style_uri = $style->buildUri($image_uri);
    if (file_exists($style_uri)) {
      return $style_uri;
    }

    $success = file_exists($style_uri) || $style->createDerivative($image_uri, $style_uri);
    return $success ? $style_uri : FALSE;
  }

  /**
   * Convert image field items into file URIs to be packaged.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $fieldItemList
   *   File field items to turn into URLs.
   * @param array $options
   *   Options, used to refine/filter behavior during packaging.
   *   See EntityProcessor.
   *
   * @return array
   *   Return an array of file uri.
   */
  public static function processEntityRefField(EntityReferenceFieldItemList $fieldItemList, array $options) {
    $uris = [];

    /** @var \Drupal\Core\Entity\EntityInterface $item */
    foreach ($fieldItemList->referencedEntities() as $item) {
      $temp = EntityProcessor::processEntity($item, $options);
      $uris = array_merge($temp, $uris);
    }

    return $uris;
  }

}
