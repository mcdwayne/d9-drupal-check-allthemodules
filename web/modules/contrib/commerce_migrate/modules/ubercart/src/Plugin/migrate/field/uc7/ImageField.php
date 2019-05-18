<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\field\uc7;

use Drupal\image\Plugin\migrate\field\d7\ImageField as CoreImageField;

/**
 * Adds Ubercart field formatter to field map.
 *
 * @MigrateField(
 *   id = "image",
 *   core = {7},
 *   source_module = "image",
 *   destination_module = "image"
 * )
 */
class ImageField extends CoreImageField {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    // Ubercart 7 provides a custom field formatter for the image field.
    return [
      'image' => 'image',
      'uc_product_image' => 'image',
    ];
  }

}
