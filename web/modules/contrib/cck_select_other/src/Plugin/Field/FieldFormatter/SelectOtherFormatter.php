<?php

namespace Drupal\cck_select_other\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'cck_select_other' formatter.
 *
 * @FieldFormatter(
 *   id = "cck_select_other",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   }
 * )
 */
class SelectOtherFormatter extends FormatterBase {

  use AllowedTagsXssTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $allowed_values = $this->getFieldSetting('allowed_values');

    foreach ($items as $delta => $item) {
      if (isset($allowed_values[$item->value])) {
        $output = $this->fieldFilterXss($allowed_values[$item->value]);
      }
      else {
        // If no match was found in allowed values, fall back to the key.
        $output = $this->fieldFilterXss($item->value);
      }
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
