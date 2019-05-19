<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'CountyDistrictFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "CountyDistrictFormatter",
 *   module = "field_addresstw",
 *   label = @Translation("Conuty and District only"),
 *   field_types = {
 *     "field_addresstw"
 *   }
 * )
 */
class CountyDistrictFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'address-tw-information-county-district',
        ],
        '#value' =>  '<span class="addresstw-county">'.$item->county.'</span><span class="addresstw-district">'.$item->district,
      ];
    }

    return $elements;
  }

}
