<?php

namespace Drupal\quadruple_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementations for 'tabs' formatter.
 *
 * @FieldFormatter(
 *   id = "quadruple_field_tabs",
 *   label = @Translation("Tabs"),
 *   field_types = {"quadruple_field"}
 * )
 */
class Tabs extends Base {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element[0] = [
      '#theme' => 'quadruple_field_tabs',
      '#items' => $items,
      '#settings' => $this->getSettings(),
      '#attached' => ['library' => ['quadruple_field/tabs']],
    ];

    return $element;
  }

}
