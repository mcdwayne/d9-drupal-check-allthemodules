<?php

namespace Drupal\ext_redirect\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'source_path_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "source_path_formatter",
 *   label = @Translation("Source Path Formatter"),
 *   field_types = {
 *     "source_path"
 *   }
 * )
 */
class SourcePathFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $paths = $item->getValue();
    $paths = Html::escape($paths);
    return nl2br($paths);
  }

}
