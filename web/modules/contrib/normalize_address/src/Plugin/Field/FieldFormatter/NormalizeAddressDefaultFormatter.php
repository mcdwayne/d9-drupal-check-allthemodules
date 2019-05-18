<?php

namespace Drupal\normalize_address\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'NormalizeAddressDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "NormalizeAddressDefaultFormatter",
 *   label = @Translation("Show Address From Google API"),
 *   field_types = {
 *     "normalize_address"
 *   }
 * )
 */
class NormalizeAddressDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_full,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_province,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_city,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_street_address,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_building_number,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_unit_number,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_postal_code,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_lattitude,
      ];
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->normalized_address_longtitude,
      ];
    }
    return $elements;
  }

}
