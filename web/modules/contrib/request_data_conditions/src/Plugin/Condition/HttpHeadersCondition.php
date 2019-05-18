<?php

namespace Drupal\request_data_conditions\Plugin\Condition;

/**
 * Provides an 'HTTP Headers' condition.
 *
 * @Condition(
 *   id = "http_headers",
 *   label = @Translation("HTTP headers")
 * )
 */
class HttpHeadersCondition extends BaseCondition
{

  /**
   * {@inheritdoc}
   */
  protected function getDataContext() {
    return $this->requestStack->getCurrentRequest()->headers->all();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    foreach ($this->configuration['conditions'] as $condition) {
      $contexts[] = 'headers:' . $condition['name'];
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Condition based on the current request's HTTP headers.");
  }

}
