<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Performance;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * CSS aggregation check
 *
 * @ProdCheck(
 *   id = "css_aggregated",
 *   title = @Translation("Aggregate and compress CSS files."),
 *   category = "performance",
 * )
 */
class CssAggregated extends ProdCheckBase {

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->configFactory->get('system.performance')->get('css.preprocess');
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Enabled'),
      'description' => $this->generateDescription(
        $this->title(),
        'system.performance_settings'
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Disabled'),
      'description' => $this->generateDescription(
        $this->title(),
        'system.performance_settings',
        'Your %link settings are disabled, they should be enabled on a production environment! This should not cause trouble if you steer clear of @import statements.'
       ),
    ];
  }

}
