<?php

namespace Drupal\description_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'description_field_default' formatter.
 *
 * @FieldFormatter(
 *   id = "description_field_default",
 *   label = @Translation("Description default formatter"),
 *   description = @Translation("A field formatter used for formatting a Description field."),
 *   field_types = {
 *     "description_field"
 *   }
 * )
 */
class DescriptionFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    return $elements;
  }

}
