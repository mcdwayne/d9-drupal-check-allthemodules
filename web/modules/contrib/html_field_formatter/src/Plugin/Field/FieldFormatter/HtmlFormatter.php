<?php

namespace Drupal\html_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'html' formatter.
 *
 * @FieldFormatter(
 *   id = "html",
 *   label = @Translation("HTML"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *   }
 * )
 */
class HtmlFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {

      $value = $item->value;
      if (empty($value)) {
        $value = $item->getValue();
      }

      $elements[$delta] = array(
        '#markup' => $value,
      );
    }

    return $elements;
  }

}
