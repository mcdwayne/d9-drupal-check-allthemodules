<?php

namespace Drupal\azure_entity_moderation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'azure_entity_moderation_number' formatter.
 *
 * @FieldFormatter(
 *   id = "azure_entity_moderation_number",
 *   label = @Translation("Exact value as a number."),
 *   field_types = {
 *     "azure_entity_moderation"
 *   }
 * )
 */
class AzureModerationNumberFormatter extends FormatterBase {

  const SENTIMENT_LEVELS = [
    [0, 'negative'],
    [0.333, 'neutral'],
    [0.666, 'positive'],
  ];

  /**
   * Calculate the sentiment level (1 - 3 scale).
   *
   * @param float $value
   *   Seltiment level returned by Azure text analysis.
   *
   * @return int
   *   Sentiment level as an integer.
   */
  protected function calculateSentiment($value) {
    for ($i = 0; $i < count(self::SENTIMENT_LEVELS); $i++) {
      if (
        $value >= self::SENTIMENT_LEVELS[$i][0] && (
          !isset(self::SENTIMENT_LEVELS[$i + 1]) ||
          $value < self::SENTIMENT_LEVELS[$i + 1][0]
        )
      ) {
        return self::SENTIMENT_LEVELS[$i][1];
      }
    }
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
        '#value' => $item->value,
        '#attributes' => [
          'class' => ['sentiment-level-' . $this->calculateSentiment($item->value)],
        ],
      ];
    }
    return $elements;
  }

}
