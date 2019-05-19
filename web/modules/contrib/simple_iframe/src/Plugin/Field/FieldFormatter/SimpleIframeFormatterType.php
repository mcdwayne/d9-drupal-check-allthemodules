<?php

namespace Drupal\simple_iframe\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'simple_iframe_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_iframe_formatter_type",
 *   label = @Translation("Simple iframe formatter type"),
 *   field_types = {
 *     "simple_iframe_field_type"
 *   }
 * )
 */
class SimpleIframeFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'simple_iframe',
        '#url' => $item->url,
        '#width' => $item->width,
        '#height' => $item->height,
      ];
    }

    return $elements;
  }

}
