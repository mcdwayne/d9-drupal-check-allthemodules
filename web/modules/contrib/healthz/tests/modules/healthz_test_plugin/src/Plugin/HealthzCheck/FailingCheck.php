<?php

namespace Drupal\healthz_test_plugin\Plugin\HealthzCheck;

use Drupal\healthz\Plugin\HealthzCheckBase;

/**
 * Provides a check that always fails.
 *
 * @HealthzCheck(
 *   id = "failing_check",
 *   title = @Translation("Failing Check")
 * )
 */
class FailingCheck extends HealthzCheckBase {

  /**
   * {@inheritdoc}
   */
  public function check() {
    $this->addError($this->t("I always fail"));
    return FALSE;
  }

}
