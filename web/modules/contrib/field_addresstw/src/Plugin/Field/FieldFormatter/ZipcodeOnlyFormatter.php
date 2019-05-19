<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'ZipcodeOnlyFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ZipcodeOnlyFormatter",
 *   module = "field_addresstw",
 *   label = @Translation("Zipcode only"),
 *   field_types = {
 *     "field_addresstw"
 *   }
 * )
 */
class ZipcodeOnlyFormatter extends FormatterBase {

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
          'class' => 'address-tw-information-zipcode',
        ],
        '#value' =>  '<span class="addresstw-zipcode">'.$item->zipcode.'</span>',
      ];
    }

    return $elements;
  }

}
