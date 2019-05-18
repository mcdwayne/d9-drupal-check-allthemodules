<?php

namespace Drupal\onehub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'onehub_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "onehub_formatter",
 *   label = @Translation("OneHub File"),
 *   field_types = {
 *     "onehub"
 *   }
 * )
 */
class OneHubFileFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $element = [
      '#theme' => 'onehub_file',
      '#attached' => [
        'library' => [
          'onehub/download-styling'
        ],
      ],
    ];

    return $element;
  }

}
