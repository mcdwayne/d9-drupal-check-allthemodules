<?php

namespace Drupal\country\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'country' formatter.
 *
 * @FieldFormatter(
 *   id = "country_default",
 *   module = "country",
 *   label = @Translation("Country"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $countries = \Drupal::service('country.field.manager')->getSelectableCountries($this->fieldDefinition);
    foreach ($items as $delta => $item) {
      if (isset($countries[$item->value])) {
        $elements[$delta] = ['#markup' => $countries[$item->value]];
      }
    }
    return $elements;
  }
}
