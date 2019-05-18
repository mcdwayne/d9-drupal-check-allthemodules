<?php

namespace Drupal\faqfield\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'faqfield_details' formatter.
 *
 * @FieldFormatter(
 *   id = "faqfield_details",
 *   label = @Translation("HTML details"),
 *   field_types = {
 *     "faqfield"
 *   }
 * )
 */
class FaqFieldDetailsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $default_format = $this->getFieldSetting('default_format');
    foreach ($items as $delta => $item) {
      // Decide whether to use the default format or the custom one.
      $format = (!empty($item->answer_format) ? $item->answer_format : $default_format);
      $elements[$delta] = [
        '#theme' => 'faqfield_details_formatter',
        '#question' => $item->question,
        '#answer' => $item->answer,
        '#answer_format' => $format,
        '#delta' => $delta,
      ];
    }

    return $elements;
  }

}
