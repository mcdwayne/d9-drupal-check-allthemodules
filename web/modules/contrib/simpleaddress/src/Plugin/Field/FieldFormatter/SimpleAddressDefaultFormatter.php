<?php

namespace Drupal\simpleaddress\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal;
use Drupal\Component\Utility\Unicode;


/**
 * Plugin implementation of the 'simpleaddress_default' formatter.
 *
 * @FieldFormatter(
 *   id = "simpleaddress_default",
 *   module = "simpleaddress",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "simpleaddress"
 *   }
 * )
 */
class SimpleAddressDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $countries = \Drupal::service('country_manager')->getList();
    $formatter = $this->getAddressFormatSettings($items);
    foreach ($items as $delta => $item) {
      $address = '';
      $address .= '<div class="addressfield">';
      $address .= '<div itemscope itemtype="http://schema.org/PostalAddress">';
      foreach($formatter as $line) {
        if(isset($item->{$line})) {
          if($line == 'addressCountry') {
            $item->{$line} = $countries[$item->{$line}];
          }
          $address .= '<span itemprop="' . $line . '">' . $item->{$line} . '</span>';
        }
      }
      $address .= '</div>';
      $address .= '</div>';

      $elements[$delta] = array('#markup' => $address);
    }
    
    return $elements;
  }

  /**
   * Get the address formatter settings from the configuration file.
   *
   * @param $items
   *
   * @return array
   */
  public function getAddressFormatSettings($items) {
    $formats = \Drupal::config('simpleaddress.settings');
    $country = isset($items[0]) ? Unicode::strtolower($items[0]->addressCountry) : '';
    $countryFormat = $formats->get($country);

    return (!empty($country) && isset($countryFormat)) ? $countryFormat : $formats->get('default');
  }

}
