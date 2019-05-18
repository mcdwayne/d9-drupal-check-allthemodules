<?php

namespace Drupal\campaignmonitor_local\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorLocalAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_local_admin_settings';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor_local.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_local.settings');

    $form['cron_interval'] = [
      '#type' => 'number',
      '#title' => t('Cron Interval'),
      '#description' => t('Enter the number of seconds as an interval between successive cron runs.'),
      '#default_value' => $config->get('cron_interval'),

    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_local.settings');
    $config
      ->set('cron_interval', $form_state->getValue('cron_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
