<?php

namespace Drupal\azure_entity_moderation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'azure_entity_moderation_number' formatter.
 *
 * @FieldFormatter(
 *   id = "azure_entity_moderation_color",
 *   label = @Translation("Color representation."),
 *   field_types = {
 *     "azure_entity_moderation"
 *   }
 * )
 */
class AzureModerationColorFormatter extends AzureModerationNumberFormatter {

  /**
   * Color map array.
   *
   * 'color' values correspond to RGB.
   */
  const COLOR_MAP = [
    ['fraction' => 0.0, 'color' => [255, 0, 0]],
    ['fraction' => 0.5, 'color' => [125, 125, 125]],
    ['fraction' => 1, 'color' => [0, 255, 0]],
  ];

  /**
   * Calculate color based on fraction.
   *
   * @param float $fraction
   *   Fraction for which the color needs to be calculated (0-1).
   *
   * @return array
   *   Color representation as RGB.
   */
  protected function calculateColor($fraction) {
    for ($i = 1; $i < count(self::COLOR_MAP); $i++) {
      if ($fraction < self::COLOR_MAP[$i]['fraction']) {
        break;
      }
    }

    $lower = self::COLOR_MAP[$i - 1];
    $upper = self::COLOR_MAP[$i];
    $range = $upper['fraction'] - $lower['fraction'];
    $rangePct = ($fraction - $lower['fraction']) / $range;
    $pctLower = 1 - $rangePct;
    $pctUpper = $rangePct;
    $color = [
      floor($lower['color'][0] * $pctLower + $upper['color'][0] * $pctUpper),
      floor($lower['color'][1] * $pctLower + $upper['color'][1] * $pctUpper),
      floor($lower['color'][2] * $pctLower + $upper['color'][2] * $pctUpper),
    ];
    return 'rgb(' . implode(',', $color) . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => '',
        '#attributes' => [
          'class' => [
            'azure-sentiment-level-color',
          ],
          'style' => 'background-color: ' . $this->calculateColor($item->value),
          'title' => $item->value,
        ],
      ];

    }
    $elements['#attached']['library'][] = 'azure_entity_moderation/moderationUi';

    return $elements;
  }

}
