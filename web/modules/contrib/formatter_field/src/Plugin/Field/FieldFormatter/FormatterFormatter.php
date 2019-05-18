<?php

namespace Drupal\formatter_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Default' formatter.
 *
 * @FieldFormatter(
 *   id = "formatter_field_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "formatter_field_formatter"
 *   }
 * )
 */
class FormatterFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta]['type'] = [
        '#type' => 'item',
        '#title' => $this->t('Type'),
        '#markup' => $item->type,
      ];
      $element[$delta]['settings'] = [
        '#type' => 'item',
        '#title' => $this->t('Settings'),
        '#markup' => var_export(unserialize($item->settings), TRUE),
      ];
    }

    return $element;
  }

}
