<?php

namespace Drupal\request_data_conditions\Plugin\Condition;

/**
 * Provides a 'Cookie values' condition.
 *
 * @Condition(
 *   id = "cookie_values",
 *   label = @Translation("Cookie values")
 * )
 */
class CookieValuesCondition extends BaseCondition
{

  /**
   * {@inheritdoc}
   */
  protected function getDataContext() {
    return $this->requestStack->getCurrentRequest()->cookies->all();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    foreach ($this->configuration['conditions'] as $condition) {
      $contexts[] = 'cookies:' . $condition['name'];
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function summary()
  {
    return $this->t("Condition based on the current request's cookie values.");
  }

}
