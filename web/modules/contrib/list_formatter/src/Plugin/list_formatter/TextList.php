<?php
/**
 * @file
 */

namespace Drupal\list_formatter\Plugin\list_formatter;

use Drupal\list_formatter\Plugin\ListFormatterListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;

/**
 * Plugin implementation of the taxonomy module.
 *
 * @ListFormatter(
 *   id = "text",
 *   module = "text",
 *   field_types = {"text_default", "text_long", "text_with_summary"}
 * )
 */
class TextList implements ListFormatterListInterface {

  /**
   * @todo.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, $langcode) {
    $list_items = [];

    if ($field_definition->getType() === 'text_long') {
      foreach ($items as $delta => $item) {
        // Explode on new line char, trim whitespace (if any), then array filter (So any empty lines will actually be removed).
        $long_text_items = array_filter(array_map('trim', explode("\n", $item['value'])));
        foreach ($long_text_items as $long_text_item) {
          $list_items[] = [
            '#type' => 'processed_text',
            '#text' => $long_text_item,
            '#format' => $item->format,
            '#langcode' => $item->getLangcode(),
          ];
        }
      }
    }
    else {
      foreach ($items as $delta => $item) {
        $list_items[] = [
          '#type' => 'processed_text',
          '#text' => $item->value,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }
    }

    return $list_items;
  }

  /**
   * @todo.
   */
  public function additionalSettings(&$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
  }

}
