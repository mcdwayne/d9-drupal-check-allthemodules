<?php

namespace Drupal\field_union\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Defines a class for field union formatter.
 *
 * @FieldFormatter(
 *   id = "field_union",
 *   label = @Translation("Field Union"),
 *   field_types = {
 *     "field_union"
 *   }
 * )
 */
class FieldUnionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo
    return [];
  }

}
