<?php

namespace Drupal\request_data_conditions\Plugin\Condition;

/**
 * Provides a 'URL query parameters' condition.
 *
 * @Condition(
 *   id = "url_query_parameters",
 *   label = @Translation("URL query parameters")
 * )
 */
class UrlQueryParametersCondition extends BaseCondition
{
  /**
   * {@inheritdoc}
   */
  protected function getDataContext() {
    return $this->requestStack->getCurrentRequest()->query->all();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    $contexts = parent::getCacheContexts();

    foreach ($this->configuration['conditions'] as $condition) {
      $contexts[] = 'url.query_args:' . $condition['name'];
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function summary()
  {
    return $this->t("Condition based on the current request's URL query parameters.");
  }

}
