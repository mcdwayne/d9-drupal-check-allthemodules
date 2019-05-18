<?php

namespace Drupal\read_more_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'Read more' formatter.
 *
 * @FieldFormatter(
 *   id = "read_more_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "read_more"
 *   }
 * )
 */
class ReadMoreDefaultFormatter extends FormatterBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays the teaser with a read more link.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      $element[$delta] = [
        '#theme' => 'read_more_field',
        '#teaser' => [
          '#type' => 'processed_text',
          '#text' => $value['teaser_value'],
          '#format' => $value['teaser_format'],
        ],
        '#label' => $items->getSetting('label'),
        '#hidden' => [
          '#type' => 'processed_text',
          '#text' => $value['hidden_value'],
          '#format' => $value['hidden_format'],
        ],
      ];
    }

    return $element;
  }

}
