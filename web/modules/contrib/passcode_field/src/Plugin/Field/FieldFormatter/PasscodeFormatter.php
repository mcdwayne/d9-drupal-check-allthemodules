<?php

namespace Drupal\passcode_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_passcode_default' formatter.
 *
 * @FieldFormatter(
 *   id = "field_passcode_default",
 *   module = "passcode_field",
 *   label = @Translation("Default formatter"),
 *   field_types = {
 *     "field_passcode"
 *   }
 * )
 */
class PasscodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if (isset($item->passcode)) {
        $elements[$delta]['#markup'] = $item->passcode;
      }
    }
    return $elements;
  }

}
