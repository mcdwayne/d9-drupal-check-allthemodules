<?php

/**
 * @file
 * Contains \Drupal\eloqua_rest_api\Form\EloquaRestApiAdminSettings.
 */

namespace Drupal\eloqua_rest_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class EloquaRestApiAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eloqua_rest_api_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('eloqua_rest_api.settings');

    foreach (Element::children($form) as $variable) {
      if (strpos($variable, 'eloqua_rest_api_') === 0) {
        $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
      }
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eloqua_rest_api.settings'];
  }

  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $config = $this->configFactory->get('eloqua_rest_api.settings');

    $form['eloqua_rest_api_site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $config->get('eloqua_rest_api_site_name'),
      '#description' => $this->t("Your company's site name in Eloqua."),
      '#required' => TRUE,
    ];

    $form['eloqua_rest_api_login_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login name'),
      '#default_value' => $config->get('eloqua_rest_api_login_name'),
      '#description' => $this->t('The user name used to verify access to the Eloqua REST API. Consider using an admin account whose credentials do not expire.'),
      '#required' => TRUE,
    ];

    $form['eloqua_rest_api_login_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login password'),
      '#default_value' => $config->get('eloqua_rest_api_login_password'),
      '#description' => $this->t('The password associated with the user above; used to verify access to the API.'),
      '#required' => TRUE,
    ];

    $form['eloqua_rest_api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('eloqua_rest_api_base_url'),
      '#description' => $this->t('The base URL of the Eloqua instance against which API calls will be made. If left blank, this will default to %example.', [
        '%example' => 'https://secure.eloqua.com'
        ]),
    ];

    $form['eloqua_rest_api_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('API timeout'),
      '#default_value' => $config->get('eloqua_rest_api_timeout'),
      '#description' => $this->t('The amount of time (in seconds) that the REST API client is allowed to wait before cancelling the request and aborting.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

}
