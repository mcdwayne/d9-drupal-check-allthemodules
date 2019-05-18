<?php

namespace Drupal\healthz_test_plugin\Plugin\HealthzCheck;

use Drupal\healthz\Plugin\HealthzCheckBase;

/**
 * Provides a check that always fails but returns a 200.
 *
 * @HealthzCheck(
 *   id = "failing_200",
 *   title = @Translation("Failing 200"),
 *   failureStatusCode = 200
 * )
 */
class Failing200 extends HealthzCheckBase {

  /**
   * {@inheritdoc}
   */
  public function check() {
    $this->addError($this->t("I always fail and return a 200"));
    return FALSE;
  }

}
