<?php

namespace Drupal\healthz_test_plugin\Plugin\HealthzCheck;

use Drupal\Core\Form\FormStateInterface;
use Drupal\healthz\Plugin\HealthzCheckBase;

/**
 * Provides a check that always passes.
 *
 * @HealthzCheck(
 *   id = "passing_check",
 *   title = @Translation("Passing check"),
 *   description = @Translation("A passing check"),
 *   settings = {
 *     "test_setting" = 0
 *   }
 * )
 */
class PassingCheck extends HealthzCheckBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'test_setting' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Test setting'),
        '#default_value' => $this->getConfiguration()['settings']['test_setting'],
      ],
    ];
  }

}
