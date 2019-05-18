<?php

namespace Drupal\background_process\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Default controller for the background_process module.
 */
class BackgroundProcessSettingsForm extends ConfigFormBase {

  /**
   * Implements to Get Form ID.
   */
  public function getFormId() {
    return 'background_process_settings_form';
  }

  /**
   * Implements to Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('background_process.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Implements to Get Editable Config Names.
   */
  protected function getEditableConfigNames() {
    return ['background_process.settings'];
  }

  /**
   * Implements to Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['background_process_service_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service timeout'),
      '#description' => $this->t('Timeout for service call in seconds (0 = disabled)'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_service_timeout'),
    ];
    $form['background_process_connection_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Connection timeout'),
      '#description' => $this->t('Timeout for connection in seconds'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_connection_timeout'),
    ];
    $form['background_process_stream_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stream timeout'),
      '#description' => $this->t('Timeout for stream in seconds'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_stream_timeout'),
    ];
    $form['background_process_redispatch_threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redispatch threshold (for locked processes)'),
      '#description' => $this->t('Seconds to wait before redispatching processes that never started.'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_redispatch_threshold'),
    ];
    $form['background_process_cleanup_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cleanup age (for locked processes)'),
      '#description' => $this->t('Seconds to wait before unlocking processes that never started.'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age'),
    ];
    $form['background_process_cleanup_age_running'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cleanup age (for running processes)'),
      '#description' => $this->t('Unlock processes that has been running for more than X seconds.'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age_running'),
    ];
    $form['background_process_cleanup_age_queue'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cleanup age for queued jobs'),
      '#description' => $this->t('Unlock queued processes that have been more than X seconds to start.'),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_cleanup_age_queue'),
    ];
    $options = background_process_get_service_hosts();
	global $base_url;
	
    foreach ($options as $key => &$value) {
      $new = empty($value['description']) ? $key : $value['description'];
      $base_url = empty($value['base_url']) ? $base_url : $value['base_url'];
      $http_host = empty($value['http_host']) ? parse_url($base_url, PHP_URL_HOST) : $value['http_host'];
      $new .= ' (' . $base_url . ' - ' . $http_host . ')';
      $value = $new;
    }
	if(!$value) {
		$value = $base_url;
	}
    $form['background_process_default_service_host'] = [
      '#type' => 'select',
      '#title' => $this->t('Default service host'),
      '#description' => $this->t('The default service host to use'),
      '#options' => [$value],
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_default_service_host'),
    ];
    $form['background_process_ssl_verification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('SSL verification'),
      '#description' => $this->t("Don't turn this off on production environments, as it will raise security issues"),
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_ssl_verification'),
    ];
    $methods = \Drupal::moduleHandler()->invokeAll('service_group');
    $options = background_process_get_service_groups();
    foreach ($options as $key => &$value) {
      $value = (empty($value['description']) ? $key : $value['description']) . ' (' . implode(',', $value['hosts']) . ') : ' . $methods['methods'][$value['method']];
    }
    $form['background_process_default_service_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Default service group'),
      '#description' => $this->t('The default service group to use.'),
      '#options' => $options,
      '#default_value' => \Drupal::config('background_process.settings')->get('background_process_default_service_group'),
    ];

    $form = parent::buildForm($form, $form_state);

    // Add determine button and make sure all the buttons are shown last.
    $form['buttons']['#weight'] = 1000;
    $form['buttons']['determine'] = [
      '#value' => $this->t("Determine default service host"),
      '#description' => $this->t('Tries to determine the default service host.'),
      '#type' => 'submit',
      '#submit' => [
        'background_process_settings_form_determine_submit',
      ],
    ];

    return $form;
  }

}
