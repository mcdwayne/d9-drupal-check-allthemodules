<?php

namespace Drupal\drd\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'ipv4field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ipv4field_formatter",
 *   label = @Translation("IP v4"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class IPv4 extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => long2ip($item->value)];
    }

    return $elements;
  }

}
