<?php

namespace Drupal\quadruple_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementations for 'accordion' formatter.
 *
 * @FieldFormatter(
 *   id = "quadruple_field_accordion",
 *   label = @Translation("Accordion"),
 *   field_types = {"quadruple_field"}
 * )
 */
class Accordion extends Base {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element[0] = [
      '#theme' => 'quadruple_field_accordion',
      '#items' => $items,
      '#settings' => $this->getSettings(),
      '#attached' => ['library' => ['quadruple_field/accordion']],
    ];

    return $element;
  }

}
