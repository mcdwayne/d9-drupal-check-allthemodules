<?php

namespace Drupal\entity_generic\Plugin\views\field;

use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * A base class for modal operations.
 */
abstract class GenericOperationModalBase extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function renderText($alter) {
    if (isset($alter['url'])) {
      $options = $alter['url']->getOptions();
      $options['attributes']['class'][] = 'use-ajax';
      $alter['url']->setOptions($options);
    }
    return parent::renderText($alter);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $build = parent::render($row);

    return $build;
  }

}
