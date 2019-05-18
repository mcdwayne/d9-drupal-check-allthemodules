<?php

namespace Drupal\onehub\Plugin\views\field;


use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;


/**
 * Displays the OneHub Field for Downloading in a view.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("onehub_views_filename")
 */
class OneHubViewsFilename extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (empty($value)) {
      return '';
    }

    $element = [
      '#theme' => 'onehub_views_file',
      '#id' => $value,
      '#index' => $values->index,
      '#attached' => [
        'library' => [
          'onehub/download-styling'
        ],
      ],
    ];

    return $element;
  }

}
