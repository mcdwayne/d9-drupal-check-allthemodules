<?php

namespace Drupal\address_phonenumber\Plugin\Field\FieldFormatter;

use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Class AddressPhoneNumberDefaultFormatter.
 *
 * @FieldFormatter(
 *   id = "address_phone_number_default",
 *   label = @Translation("Address with Phonenumber"),
 *   description = @Translation("Display the reference entities’ label with their address phonenumber."),
 *   field_types = {
 *     "address_phone_number_item"
 *   }
 * )
 */
class AddressPhoneNumberDefaultFormatter extends AddressDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();
    foreach ($elements as $delta => $entity) {
      $elements[$delta]['#suffix'] = '<br><span class="ct-addr">' . $values[$delta]['address_phonenumber'] . '</span>';
    }
    return $elements;
  }

}
