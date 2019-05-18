<?php

/**
 * @file
 * Contains ....
 */

namespace Drupal\list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\list_formatter\Plugin\ListFormatterListInterface;

/**
 * Default list implementation plugin.
 *
 * @ListFormatter(
 *   id = "default",
 *   module = "list_formatter"
 * )
 */
class DefaultList implements ListFormatterListInterface {

  /**
   * Implements \Drupal\list_formatter\Plugin\ListFormatterListInterface::createList().
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, $langcode) {
    $list_items = [];

    // Use our helper function to get the value key dynamically.
    $value_key = $field_definition->getFieldStorageDefinition()->getMainPropertyName();

    foreach ($items as $delta => $item) {
      $list_items[$delta] = [
        '#markup' => $item->{$value_key},
        '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
      ];;
    }

    return $list_items;
  }

  /**
   * @todo.
   */
  public function additionalSettings(&$elements, FieldDefinitionInterface $field_definition, FormatterInterface $formatter) {
  }

}
