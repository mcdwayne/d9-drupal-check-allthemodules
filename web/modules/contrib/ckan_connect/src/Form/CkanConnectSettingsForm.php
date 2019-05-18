<?php

namespace Drupal\ckan_connect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Configures CKAN Connect settings for this site.
 */
class CkanConnectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckan_connect_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ckan_connect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckan_connect.settings');

    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API settings'),
      '#tree' => TRUE,
    ];

    $form['api']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#description' => $this->t('Specify the endpoint URL. Example https://data.gov.au/api/3 (please note no trailing slash).'),
      '#default_value' => $config->get('api.url'),
      '#required' => TRUE,
    ];

    $form['api']['key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('Optionally specify an API key.'),
      '#default_value' => $config->get('api.key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->validateApiUrl($form, $form_state);
    $this->validateApiKey($form, $form_state);
  }

  /**
   * Validates the API URL field.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateApiUrl(array &$form, FormStateInterface $form_state) {
    $api_url = $form_state->getValue(['api', 'url']);
    $api_key = $form_state->getValue(['api', 'key']);

    if (!empty($api_key)) {
      /** @var \Drupal\Core\File\FileSystem $file_system */
      $file_system = \Drupal::service('file_system');

      if ($file_system->uriScheme($api_url) !== 'https') {
        $form_state->setErrorByName('api_url', $this->t('If using an API key, the API URL must use HTTPS.'));
      }
    }
  }

  /**
   * Validates the API Key field.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateApiKey(array &$form, FormStateInterface $form_state) {
    $api_url = $form_state->getValue(['api', 'url']);
    $api_key = $form_state->getValue(['api', 'key']);

    try {
      /** @var \Drupal\ckan_connect\Client\CkanClientInterface $client */
      $client = \Drupal::service('ckan_connect.client');
      $client->setApiUrl($api_url);

      if ($api_key) {
        $client
          ->setApiKey($api_key)
          ->get('action/dashboard_activity_list', ['limit' => 1]);
      }
      else {
        $client->get('action/site_read');
      }
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      $status_code = $response->getStatusCode();

      switch ($status_code) {
        case 403:
          $form_state->setErrorByName('api_url', $this->t('API return "Not Authorised" please check your API key.'));
          break;

        default:
          $form_state->setErrorByName('api_url', $this->t('Could not establish a connection to the endpoint. Error: @code', ['@code' => $status_code]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckan_connect.settings');
    $config
      ->set('api.url', $form_state->getValue(['api', 'url']))
      ->set('api.key', $form_state->getValue(['api', 'key']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
