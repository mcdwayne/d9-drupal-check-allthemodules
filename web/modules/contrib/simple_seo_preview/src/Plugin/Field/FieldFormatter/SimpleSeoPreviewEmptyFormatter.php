<?php

namespace Drupal\simple_seo_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'simple_seo_preview_empty_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_seo_preview_empty_formatter",
 *   label = @Translation("Empty formatter"),
 *   field_types = {
 *     "simple_seo_preview"
 *   }
 * )
 */
class SimpleSeoPreviewEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    return $elements;
  }

}
