<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image with class' formatter.
 *
 * @FieldFormatter(
 *   id = "image_class",
 *   label = @Translation("Image (with class)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageClassFormatter extends ImageFormatter {

  use ElementEntityClassTrait;

}
