<?php

namespace Drupal\onehub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'onehub__select_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "onehub_select_formatter",
 *   label = @Translation("OneHub Select"),
 *   field_types = {
 *     "onehub_select"
 *   }
 * )
 */
class OneHubSelectFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $element = [
      '#theme' => 'onehub_select',
      '#attached' => [
        'library' => [
          'onehub/download-styling'
        ],
      ],
    ];

    return $element;
  }

}
