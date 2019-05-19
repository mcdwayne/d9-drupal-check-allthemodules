<?php

namespace Drupal\text_with_title\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'TextWithTitleDefaultFieldFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "text_with_title_formatter",
 *   label = @Translation("List"),
 *   field_types = {
 *     "text_with_title_field"
 *   }
 * )
 */
class TextWithTitleFormatter extends FormatterBase {

  /**
   * Define how the field type is displayed.
   *
   * Inside this method we can customize how the field is displayed inside
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        'title' => [
          '#plain_text' => $item->title,
        ],
        'text' => [
          '#type' => 'processed_text',
          '#text' => $item->text['value'],
          '#format' => $item->text['format'],
          '#langcode' => $langcode,
        ],
      ];
    }
    return $elements;
  }

}
