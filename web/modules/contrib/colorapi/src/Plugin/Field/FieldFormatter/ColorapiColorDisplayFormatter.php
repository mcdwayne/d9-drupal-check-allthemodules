<?php

namespace Drupal\colorapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Colored block formatter for Color API Color fields.
 *
 * @FieldFormatter(
 *   id = "colorapi_color_display",
 *   label = @Translation("Colored Block"),
 *   field_types = {
 *      "colorapi_color_field"
 *   }
 * )
 */
class ColorapiColorDisplayFormatter extends ColorapiDisplayFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary['overview'] = $this->t('Displays the color in a colored block/square/div');

    return $summary + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {

      $this->addHumanReadableNameToElement($element, $delta, $item);

      $element[$delta]['color'] = [
        '#theme' => 'colorapi_color_display',
        '#entity_delta' => $delta,
        '#item' => $item,
        '#hexadecimal_color' => $item->getHexadecimal(),
      ];
    }

    return $element;
  }

}
