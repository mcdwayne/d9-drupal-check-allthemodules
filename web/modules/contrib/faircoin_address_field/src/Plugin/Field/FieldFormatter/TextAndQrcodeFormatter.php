<?php

/**
 * @file
 * Drupal\faircoin_address_field\Plugin\Field\FieldFormatter\TextAndQrcodeFormatter.
 */

namespace Drupal\faircoin_address_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
/**
 * Implementation of 'faircoin_address_field_text_and_qrcode' formatter.
 *
 * @FieldFormatter(
 *   id = "faircoin_address_field_text_and_qrcode",
 *   module = "faircoin_address_field",
 *   label = @Translation("Text and QR code formatter"),
 *   field_types = {
 *     "faircoin_address"
 *   }
 * )
 */
class TextAndQrcodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        'faircoin-address-qrcode-icon' => array(
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => array(
            'class' => 'faircoin-address-qrcode-icon',
          ),
          '#value' => '',
        ),
        'faircoin-address-qrcode-text' => array(
          '#type' => 'html_tag',
          '#tag' => 'code',
          '#attributes' => array(
            'class' => 'faircoin-address-qrcode-text',
          ),
          '#value' => $item->value,
        ),
        'faircoin-address-qrcode-image' => array(
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => array(
            'class' => 'faircoin-address-qrcode-wrapper',
          ),
          '#value' => '<div class="faircoin-address-qrcode-image"></div>',
        ),
        '#attached' => array(
          'library' => array(
            'faircoin_address_field/text-to-qrcode',
          ),
        ),
      );
    }
    return $elements;
  }

}
