<?php

namespace Drupal\quadruple_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementations for 'quadruple_field' formatter.
 *
 * @FieldFormatter(
 *   id = "quadruple_field_unformatted_list",
 *   label = @Translation("Unformatted list"),
 *   field_types = {"quadruple_field"}
 * )
 */
class UnformattedList extends ListBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $element['#attributes']['class'][] = 'quadruple-field-unformatted-list';

    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {
      if ($settings['inline']) {
        $item->_attributes += ['class' => ['container-inline']];
      }
      $element[$delta] = [
        '#settings' => $settings,
        '#item' => $item,
        '#theme' => 'quadruple_field_item',
      ];
    }

    return $element;
  }

}
