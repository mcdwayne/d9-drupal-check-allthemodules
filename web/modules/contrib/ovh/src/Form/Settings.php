<?php

namespace Drupal\ovh\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ovh\OvhHelper;
use Drupal\ovh\Entity\OvhKey;
use Drupal\Core\Url;

/**
 *
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'ovh_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [OvhHelper::getConfigName()];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(OvhHelper::getConfigName());

    // Load default api key.
    $default_apikey = OvhKey::load($config->get('default_apikey'));
    $form['default_apikey'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'ovh_api_key',
      '#title' => 'Default API key',
      '#default_value' => $default_apikey,
      '#description' => $this->t('Set the default api key. (<a href="@link">Add new API key</a>)', ['@link' => Url::fromRoute('ovh.ovh_api_key.add')->toString()]),
    ];

    $form['generate'] = [
      '#type' => 'details',
      '#title' => 'Default application key',
      '#open' => TRUE,
      '#description' => $this->t('Create your applicaiton from : (<a href="@link">@link</a>)', ['@link' => 'https://api.ovh.com/createApp/']),
    ];

    $form['generate']['app_key'] = [
      '#type' => 'textfield',
      '#title' => 'Default application Key',
      '#maxlength' => 255,
      '#default_value' => $config->get('app_key'),
    ];

    $form['generate']['app_sec'] = [
      '#type' => 'textfield',
      '#title' => 'Default application Secret',
      '#maxlength' => 255,
      '#default_value' => $config->get('app_sec'),
    ];
    $form['generate']['endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Endpoint'),
      '#options' => $config->get('endpoints'),
      '#default_value' => $config->get('endpoint'),
      '#description' => $this->t('More on GitHub : <a href="@link">@link</a>', ['@link' => 'https://github.com/ovh/php-ovh/#supported-apis']),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => "Save Settings",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getValue('default_apikey')) {
      $form_state->setErrorByName('default_apikey', "Default API key entity is empty");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = [
      'default_apikey',
      'app_key',
      'app_sec',
      'endpoint',
    ];
    $config = $this->config(OvhHelper::getConfigName());
    foreach ($list as $item) {
      $config->set($item, $form_state->getValue($item));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
