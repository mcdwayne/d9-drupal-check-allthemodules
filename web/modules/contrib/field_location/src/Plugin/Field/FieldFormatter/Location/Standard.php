<?php

namespace Drupal\field_location\Plugin\Field\FieldFormatter\Location;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_location\Plugin\Field\FieldType\Location;

/**
 * Provides "location_formatter_standard" field formatter.
 *
 * @FieldFormatter(
 *   id = "location_formatter_standard",
 *   label = @Translation("Location (standard)"),
 *   field_types = {
 *     "location",
 *   },
 * )
 */
class Standard extends FormatterBase {

  /**
   * {@inheritdoc}
   *
   * @param Location[] $items
   *   The field values to be rendered.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{{ locality | nl2br }}',
        '#context' => $item->getValue(),
      ];
    }

    return $elements;
  }

}
