<?php

namespace Drupal\helper;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Field helper.
 */
class Field {

  /**
   * Finds duplicate field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field list class.
   * @param string $property
   *   The field item property to use. Defaults to mainPropertyName() on the
   *   field class if not provided.
   *
   * @return mixed[]
   *   An array of duplicate field values.
   */
  public static function getDuplicateValues(FieldItemListInterface $items, $property = NULL) {
    $values = [];

    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      if (!isset($property)) {
        $property = $item::mainPropertyName();
      }

      if (isset($item->{$property})) {
        $values[] = (string) $item->{$property};
      }
    }

    $value_counts = array_count_values($values);
    $duplicates = array_filter($value_counts, function ($count) {
      return $count > 1;
    });
    return array_keys($duplicates);
  }

}
