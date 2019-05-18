<?php

namespace Drupal\health_check_url\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure health settings for this site.
 */
class HealthCheckSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'health_check_url_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'health_check_url.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('health_check_url.settings');
    $options = [
      "timestamp" => time(),
      "string" => $config->get('string'),
      "stringWithTimestamp" => $config->get('string') .' - ' . time(),
      "stringWithDateTime" => $config->get('string') .' ' . strftime("at %T on %D"),
      "stringWithDateTimestamp" => $config->get('string') . ' ' . strftime("at %T on %D") . ' (' . time() . ')',
    ];
    $form['type'] = [
      '#title' => $this->t('Response Type'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $config->get('type'),
    ];

    $form['string'] = [
      '#title' => $this->t('Text'),
      '#type' => 'textfield',
      '#description' => $this->t("Enter the text to display in output. works only if the above selected Response type is contains text"),
      '#default_value' => $config->get('string'),
    ];

    $form['endpoint'] = [
      '#title' => $this->t('Endpoint'),
      '#type' => 'textfield',
      '#description' => $this->t("Enter the path for health check up"),
      '#default_value' => $config->get('endpoint'),
    ];

    $form['maintainence_access'] = [
      '#title' => $this->t('Accessible on maintainence mode'),
      '#type' => 'checkbox',
      '#description' => $this->t("Defines whether the endpoint is accessible when site is on maintainence mode "),
      '#default_value' => $config->get('maintainence_access'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('endpoint')) {
      $form_state->setValueForElement($form['endpoint'], '/health');
    }

    if ($form_state->isValueEmpty('string')) {
      $form_state->setValueForElement($form['string'], 'Passed');
    }

    if (($value = $form_state->getValue('endpoint')) && $value[0] !== '/') {
      $form_state->setErrorByName('endpoint', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('endpoint')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('health_check_url.settings')
      ->set('type', $form_state->getValue('type'))
      ->set('string', $form_state->getValue('string'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('maintainence_access', $form_state->getValue('maintainence_access'))
      ->save();
    \Drupal::service("router.builder")->rebuild();
    parent::submitForm($form, $form_state);
  }

}
