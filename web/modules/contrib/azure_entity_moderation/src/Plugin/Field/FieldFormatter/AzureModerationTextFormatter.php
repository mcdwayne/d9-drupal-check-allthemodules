<?php

namespace Drupal\azure_entity_moderation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'azure_entity_moderation_text' formatter.
 *
 * @FieldFormatter(
 *   id = "azure_entity_moderation_text",
 *   label = @Translation("Display text representation."),
 *   field_types = {
 *     "azure_entity_moderation"
 *   }
 * )
 */
class AzureModerationTextFormatter extends AzureModerationNumberFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $level_mappings = [
      'negative' => $this->t('Negative'),
      'neutral' => $this->t('Neutral'),
      'positive' => $this->t('Positive'),
    ];

    foreach ($items as $delta => $item) {
      $level = $this->calculateSentiment($item->value);

      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $level_mappings[$level],
        '#attributes' => [
          'class' => [
            'azure-sentiment-level-text',
            'sentiment-level-' . $level,
          ],
        ],
      ];
    }
    return $elements;
  }

}
