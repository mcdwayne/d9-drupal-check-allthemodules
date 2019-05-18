<?php

namespace Drupal\queue_monitor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QueueMonitorSettingsForm
 * @package Drupal\queue_monitor\Form
 */
class QueueMonitorSettingsForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'queue_monitor_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'queue_monitor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('queue_monitor.settings');
    $form['sleep'] = array(
      '#type' => 'number',
      '#title' => $this->t('sleep'),
      '#description' => $this->t('scan queue sleep (second)'),
      '#default_value' => $config->get('sleep'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory');
    $config->getEditable('queue_monitor.settings')
      ->set('sleep', $form_state->getValue('sleep'))
      ->save();

  }
}
