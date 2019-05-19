<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'FieldAddresstwFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "FieldAddresstwFormatter",
 *   module = "field_addresstw",
 *   label = @Translation("Full Taiwan Address Text"),
 *   field_types = {
 *     "field_addresstw"
 *   }
 * )
 */
class FieldAddresstwFormatter extends FormatterBase {

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
          'class' => 'address-tw-information',
        ],
        '#value' =>  '<span class="addresstw-zipcode">'.$item->zipcode.'</span><span class="addresstw-county">'.$item->county.'</span><span class="addresstw-district">'.$item->district.'</span><span class="addresstw-address">'.$item->addresstw.'</span>',
      ];
    }

    return $elements;
  }

}
