<?php

namespace Drupal\field_addresstw\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'SimpleFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "SimpleFormatter",
 *   module = "field_addresstw",
 *   label = @Translation("Simple Taiwan Address Text"),
 *   field_types = {
 *     "field_addresstw"
 *   }
 * )
 */
class SimpleFormatter extends FormatterBase {

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
          'class' => 'address-tw-information-simple',
        ],
        '#value' =>  '<span class="addresstw-county">'.$item->county.'</span><span class="addresstw-district">'.$item->district.'</span><span class="addresstw-address">'.$item->addresstw.'</span>',
      ];
    }

    return $elements;
  }

}
