<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Performance;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * JS aggregation check
 *
 * @ProdCheck(
 *   id = "js_aggregated",
 *   title = @Translation("Aggregate and compress JS files."),
 *   category = "performance",
 * )
 */
class JsAggregated extends ProdCheckBase {

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->configFactory->get('system.performance')->get('js.preprocess');
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
