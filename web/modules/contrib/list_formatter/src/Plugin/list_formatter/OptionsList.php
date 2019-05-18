<?php

/**
 * @file
 * Contains \...
 */

namespace Drupal\list_formatter\Plugin\list_formatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\list_formatter\Plugin\ListFormatterListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Plugin implementation of the options module.
 *
 * @ListFormatter(
 *   id = "options",
 *   module = "options",
 *   field_types = {"list_boolean", "list_float", "list_integer", "list_string"}
 * )
 */
class OptionsList implements ListFormatterListInterface {

  /**
   * @todo.
   */
  public function createList(FieldItemListInterface $items, FieldDefinitionInterface $field_definition, $langcode) {
    $list_items = [];

    // Only collect allowed options if there are actually items to display.
    if ($items->count()) {
      $provider = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $items->getEntity());
      // Flatten the possible options, to support opt groups.
      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      foreach ($items as $delta => $item) {
        $value = $item->value;
        // If the stored value is in the current set of allowed values, display
        // the associated label, otherwise just display the raw value.
        $output = isset($options[$value]) ? $options[$value] : $value;
        $list_items[] = [
          '#markup' => $output,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
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
