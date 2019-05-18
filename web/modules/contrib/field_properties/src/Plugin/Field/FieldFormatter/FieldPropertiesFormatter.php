<?php

/**
 * @file
 * Definition of Drupal\field_properties\Plugin\Field\FieldPropertiesFormatter.
 */

namespace Drupal\field_properties\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'field_properties_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_properties_formatter",
 *   label = @Translation("Properties"),
 *   field_types = {
 *     "field_properties"
 *   }
 * )
 */
class FieldPropertiesFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $header = array(
      t('Name'),
      t('Type'),
      t('Value'),
    );

    $rows = array();

    foreach ($items as $item) {
      $row = array(
        $item->name,
        $item->type,
        $item->value,
      );
      $rows[] = $row;
    }

    $element[0] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    );

    return $element;

  }

}
