<?php

namespace Drupal\log_monitor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TestForm
 * For testing and debug things without running cron
 *
 * @package Drupal\log_monitor\Form
 */
class TestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_monitor_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all form elements from Scheduler plugins
    $form['button'] = [
      '#type' => 'submit',
      '#value' => t('Test'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('log_monitor.storage_manager')->processLogQueue();
    \Drupal::service('log_monitor.schedule_manager')->validate();
    \Drupal::service('log_monitor.cleanup_manager')->clean();
  }

}
