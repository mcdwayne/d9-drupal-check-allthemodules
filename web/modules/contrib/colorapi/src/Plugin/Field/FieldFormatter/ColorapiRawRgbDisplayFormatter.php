<?php

namespace Drupal\colorapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Raw RGB formatter for Color API Color fields.
 *
 * @FieldFormatter(
 *   id = "colorapi_raw_rgb_display",
 *   label = @Translation("Raw RGB"),
 *
 *   field_types = {
 *      "colorapi_color_field"
 *   }
 * )
 */
class ColorapiRawRgbDisplayFormatter extends ColorapiDisplayFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary['overview'] = $this->t('Displays a RGB representation of the color, with no HTML wrappers.');

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
        '#markup' => 'RGB(' . $item->getRed() . ', ' . $item->getGreen() . ', ' . $item->getBlue() . ')',
      ];
    }

    return $element;
  }

}
