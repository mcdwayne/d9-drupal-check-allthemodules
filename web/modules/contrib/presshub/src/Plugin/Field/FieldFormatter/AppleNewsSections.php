<?php

namespace Drupal\presshub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_apple_news_sections_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_apple_news_sections_formatter",
 *   module = "presshub",
 *   label = @Translation("Apple News Sections"),
 *   field_types = {
 *     "field_apple_news_sections"
 *   }
 * )
 */
class AppleNewsSections extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => !empty($item->section_id) ? $item->section_id : $this->t('Please configure Presshub module.'),
      ];
    }

    return $elements;
  }

}
