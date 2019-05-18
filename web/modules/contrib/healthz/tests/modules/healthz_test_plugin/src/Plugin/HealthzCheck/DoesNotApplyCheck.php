<?php

namespace Drupal\healthz_test_plugin\Plugin\HealthzCheck;

use Drupal\healthz\Plugin\HealthzCheckBase;

/**
 * Provides a check that always passes but does not apply.
 *
 * @HealthzCheck(
 *   id = "does_not_apply",
 *   title = @Translation("Does not apply check")
 * )
 */
class DoesNotApplyCheck extends HealthzCheckBase {

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return FALSE;
  }

}
