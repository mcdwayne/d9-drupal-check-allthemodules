<?php

/**
 * @file
 * Contains Drupal\acquia_cloud_dashboard\Form\ConfigureForm.
 */

namespace Drupal\acquia_cloud_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;

class ConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'acquia_cloud_dashboard_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('acquia_cloud_dashboard.settings');

    $form['acquia_cloud_dashboard_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Acquia Cloud Username'),
      '#default_value' => $config->get('username', ''),
      '#description' => t('Enter your cloud username as shown on http://insight.acquia.com'),
      '#size' => '40',
      '#required' => TRUE,
      '#attributes' => array('autocomplete' => 'off'),
    );

    $form['acquia_cloud_dashboard_password'] = array(
      '#type' => 'textfield',
      '#title' => t('Acquia Cloud Password'),
      '#default_value' => $config->get('password', ''),
      '#description' => t('Enter your cloud password as shown on http://insight.acquia.com'),
      '#size' => '40',
      '#required' => TRUE,
      '#attributes' => array('autocomplete' => 'off'),
    );

    $form['acquia_cloud_dashboard_refresh_interval'] = array(
      '#type' => 'textfield',
      '#title' => t('Report Refresh Interval (seconds)'),
      '#default_value' => $config->get('refresh_interval') ?: '3600',
      '#description' => t('Enter the time interval at which the report should be refreshed.'),
      '#size' => '5',
      '#required' => TRUE,
    );

    $form['acquia_cloud_dashboard_no_of_tasks'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of recent tasks from tasklist to show'),
      '#default_value' => $config->get('task_limit') ?: '25',
      '#description' => t('Show the latest tasks from Task List, as seen on your Workflow page on Dashboard'),
      '#size' => '5',
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('acquia_cloud_dashboard.settings')
      ->set('username', $form_state['values']['acquia_cloud_dashboard_username'])
      ->set('password', $form_state['values']['acquia_cloud_dashboard_password'])
      ->set('refresh_interval', $form_state['values']['acquia_cloud_dashboard_refresh_interval'])
      ->set('task_limit', $form_state['values']['acquia_cloud_dashboard_no_of_tasks'])
      ->set('invalid_credentials', FALSE)
      ->save();

    parent::submitForm($form, $form_state);
  }

}