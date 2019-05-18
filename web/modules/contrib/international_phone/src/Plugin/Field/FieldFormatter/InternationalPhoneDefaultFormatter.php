<?php

namespace Drupal\international_phone\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'AddressDefaultFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "InternationalPhoneDefaultFormatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "international_phone"
 *   }
 * )
 */
class InternationalPhoneDefaultFormatter extends FormatterBase {

  /**
   * Define how the field type is showed.
   *
   * Inside this method we can customize how the field is displayed inside
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $text = '';

      if (isset($item->value)) {
        $text = SafeMarkup::checkPlain($item->value);
        // iPhone Support.
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== FALSE) {
          $text = '<a href="tel:' . $text . '">' . $text . '</a>';
        }
      }

      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $text,
        '#attached' => [
          'library' => ['international_phone/international_phone'],
        ],
      ];
    }
    return $elements;
  }

}
