<?php

namespace Drupal\dimension\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;
use Drupal\dimension\Plugin\Field\Basic;

/**
 * Plugin implementation of the 'number_decimal' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "number_decimal",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
abstract class Dimension extends DecimalFormatter implements Basic {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getFieldSetting('value');

    foreach ($items as $delta => $item) {
      $output = $this->numberFormat($item->value);

      // Account for prefix and suffix.
      if ($this->getSetting('prefix_suffix')) {
        $prefixes = isset($settings['prefix']) ? array_map(array('Drupal\Core\Field\FieldFilteredMarkup', 'create'), explode('|', $settings['prefix'])) : array('');
        $suffixes = isset($settings['suffix']) ? array_map(array('Drupal\Core\Field\FieldFilteredMarkup', 'create'), explode('|', $settings['suffix'])) : array('');
        $prefix = (count($prefixes) > 1) ? $this->formatPlural($item->value, $prefixes[0], $prefixes[1]) : $prefixes[0];
        $suffix = (count($suffixes) > 1) ? $this->formatPlural($item->value, $suffixes[0], $suffixes[1]) : $suffixes[0];
        $output = $prefix . $output . $suffix;
      }
      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->value != $output) {
        $item->_attributes += array('content' => $item->value);
      }

      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

}
