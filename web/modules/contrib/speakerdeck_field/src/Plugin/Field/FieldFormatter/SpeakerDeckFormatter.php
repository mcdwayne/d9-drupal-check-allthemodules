<?php

namespace Drupal\speakerdeck_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of 'speakerdeck_formatter'.
 *
 * @FieldFormatter(
 *   id = "speakerdeck_formatter",
 *   label = @Translation("SpeakerDeck embed"),
 *   field_types = {
 *     "speakerdeck_field"
 *   }
 * )
 */
class SpeakerDeckFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      if (empty($item->data_id) || empty($item->data_ratio)) {
        continue;
      }

      $element[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => [
          'async' => TRUE,
          'class' => 'speakerdeck-embed',
          'data-id' => $item->data_id,
          'data-ratio' => $item->data_ratio,
          'src' => '//speakerdeck.com/assets/embed.js'
        ],
      ];
    }

    return $element;
  }

}
