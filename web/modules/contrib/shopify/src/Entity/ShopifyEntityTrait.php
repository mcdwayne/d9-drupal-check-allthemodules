<?php

namespace Drupal\shopify\Entity;

/**
 * Class ShopifyEntityTrait.
 */
trait ShopifyEntityTrait {

  /**
   * Format date as timestamp.
   */
  public static function formatDatetimeAsTimestamp(array $fields, array &$values = []) {
    foreach ($fields as $field) {
      if (isset($values[$field]) && !is_int($values[$field])) {
        $values[$field] = strtotime($values[$field]);
      }
    }
  }

  /**
   * Sets up product image.
   */
  public static function setupProductImage($image_url) {
    $directory = file_build_uri('shopify_images');
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      // If our directory doesn't exist and can't be created, use the default.
      $directory = NULL;
    }
    $file = system_retrieve_file($image_url, $directory, TRUE, FILE_EXISTS_REPLACE);
    return $file;
  }

}
