<?php

namespace Drupal\uuid_extra\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'uuid' formatter.
 *
 * @FieldFormatter(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   field_types = {
 *     "uuid",
 *   },
 * )
 */
class UuidFieldFormatter extends FormatterBase {

  
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->value,
      ];
    }
    return $elements;
  }

}
