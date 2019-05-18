<?php

namespace Drupal\drd\Plugin\Block;

/**
 * Provides a 'WidgetCores' block.
 *
 * @Block(
 *  id = "drd_cores",
 *  admin_label = @Translation("DRD Cores"),
 *  weight = -7,
 *  tags = {"drd_widget"},
 * )
 */
class WidgetCores extends WidgetEntities {

  /**
   * {@inheritdoc}
   */
  protected function title() {
    return $this->t('Cores');
  }

  /**
   * {@inheritdoc}
   */
  protected function type() {
    return 'core';
  }

}
