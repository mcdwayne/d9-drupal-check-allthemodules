<?php

namespace Drupal\rel_content\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'plugin_reference_id' formatter.
 *
 * @FieldFormatter(
 *   id = "list_rel_content_id",
 *   label = @Translation("List rel content ID"),
 *   field_types = {
 *     "list_rel_content"
 *   }
 * )
 */
class ListRelContentIdFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode):array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => 'test me please'];
    }

    return $elements;
  }

}
