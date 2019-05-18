<?php

namespace Drupal\request_data_conditions\Plugin\Condition;

/**
 * Provides a 'Session values' condition.
 *
 * @Condition(
 *   id = "session_values",
 *   label = @Translation("Session values")
 * )
 */
class SessionValuesCondition extends BaseCondition
{

  /**
   * {@inheritdoc}
   */
  protected function getDataContext() {
    if ($session = $this->requestStack->getCurrentRequest()->getSession()) {
      return $session->all();
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    if (count($this->configuration['conditions']) > 0) {
      $contexts[] = 'session';
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Condition based on the current session's values.");
  }

}
