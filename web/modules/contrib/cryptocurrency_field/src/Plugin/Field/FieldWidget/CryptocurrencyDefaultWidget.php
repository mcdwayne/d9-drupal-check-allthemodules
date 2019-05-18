<?php

namespace Drupal\cryptocurrency_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * A cryptocurrency_field widget.
 *
 * @FieldWidget(
 *   id = "cryptocurrency_default_widget",
 *   label = @Translation("Default cryptocurrency widget"),
 *   field_types = {
 *     "cryptocurrency_field",
 *     "string"
 *   }
 * )
 */

class CryptocurrencyDefaultWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#element_validate' => [
        [static::class, 'validate'],
      ],
    ];
    return ['value' => $element];
  }

  /**
   * Validate the address.
   */
  public static function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
//  switch($currency_type) {
//    case 'bitcoin_cash':
        self::validate_bitcoin_cash($value, $form_state);
//      break;
//    case 'bitcoin_legacy':
//      self::validate_bitcoin_segwit($value, $form_state);
//      break;
//  }
  }

  private static function validate_bitcoin_cash($value, $form_state) {
    if (!preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $value)) {
      // Legacy Format
      self::validate_bitcoin_legacy($value, $form_state);
    } else {
      $char_list = "qpzry9x8gf2tvdw0s3jn54khce6mua7l";
      // Cashaddr format is prefix:payload
      // Payload is 42 characters
      $prefix = substr($value, 0, -43);
      $payload = substr($value, -42);
      // Reject if a mix of upper and lower case.
      if (strtoupper(strtolower($payload)) == $payload || strtoupper(strtoupper($payload)) == $payload) {
        $form_state->setError($element, t("Address cannot have a mix of upper and lowercase."));
        return;
      }
      $payload = strtolower($payload);
      if (preg_match('/[qp]{1}[02-9ac-hj-np-z]{41}$/', $value)) {
        // First character must be p or q
        // Find the value of the 3 most significant bits of the second address character
        $size_bits = strpos($char_list, substr($payload, 1, 1)) & 0b11100; 
        $hash_size = $size_bits < 4 ? 160 + 32 * $size_bits : 64 *($size_bits + 1);
        // Verify Checksum
        $str = self::prepare_checksum($value);
        $polymod = self::Polymod($str);
        if ($polymod !== 0) {
          $form_state->setError($element, t("Checksum does not match."));
          return;
        }
        // Extract legacy address from cashaddr.
        $value = self::convertCashaddrToLegacy($value); 
        self::validate_bitcoin_legacy($value, $form_state);
      } else {
        $form_state->setError($element, t("Address is invalid."));
        return;
      }
    }
  }

  private static function convertCashAddrToLegacy($value) {
    return $value;
  }

  private static function validate_bitcoin_segwit($value, $form_state) {
  }

  private static function validate_bitcoin_legacy($value, $form_state) {
  }

  private static function prepare_checksum($value) {
    $char_list = "qpzry9x8gf2tvdw0s3jn54khce6mua7l";
    $separator_position = strpos($value, ":");
    $prefix = substr($value, 0, $separator_position);
    $payload = substr($value, $separator_position + 1);
    $str = [];
    $length = strlen($prefix);
    for ($i = 0; $i < $length; $i++) {
      $chr = substr($prefix, $i, 1);
      $str[] = ord($chr) & 0b11111;
    }
    $str[] = 0;
    $payload_length = strlen($payload); // Should be 42.
    for ($i = 0; $i < $payload_length; $i++) {
      $chr = substr($payload, $i, 1);
        
      $str[] = strpos($char_list, $chr); 
    }
    return $str;
  }
 
  /**
   * Polymod Function
   *
   * $v an array of 5 bit numbers
   * returns a 40 bit number
   */ 
  private static function Polymod($v) {
    $c = 1;
    foreach ($v as $d) {
      $c0 = $c >> 35;
      $c = (($c & 0x07ffffffff) << 5) ^ $d;
      if ($c0 & 0x01) $c ^= 0x98f2bc8e61;
      if ($c0 & 0x02) $c ^= 0x79b76d99e2;
      if ($c0 & 0x04) $c ^= 0xf33e5fb3c4;
      if ($c0 & 0x08) $c ^= 0xae2eabe2a8;
      if ($c0 & 0x10) $c ^= 0x1e4f43e470;
    }
    return $c ^ 1;
  }

}
