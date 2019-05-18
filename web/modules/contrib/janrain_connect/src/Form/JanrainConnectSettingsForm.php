<?php

namespace Drupal\janrain_connect\Form;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;

/**
 * Form for configure messages.
 */
class JanrainConnectSettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');
    $flow_name = $config->get('flow_name');
    $flow_version = $config->get('flow_version');
    $default_language = $config->get('default_language');

    $languages = $this->languageManager->getLanguages();

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];

    $form['general']['application_id'] = [
      '#title' => $this->t('Application ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('application_id'),
      '#description' => $this->t('Enter your Janrain Application ID.'),
      '#required' => TRUE,
    ];

    $form['general']['client_id'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('It is recommended that you use an API Client with login_client role for security reasons.'),
      '#required' => TRUE,
    ];

    $form['general']['client_secret'] = [
      '#title' => $this->t('Client Secret'),
      '#type' => 'password',
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Enter the client secret of your client id.'),
      '#required' => TRUE,
    ];

    $form['general']['capture_server_url'] = [
      '#title' => $this->t('Capture Server URL'),
      '#type' => 'textfield',
      '#default_value' => $config->get('capture_server_url'),
      '#description' => $this->t('https://my-app.janraincapture.com'),
      '#required' => TRUE,
    ];

    $form['general']['entity_type'] = [
      '#title' => $this->t('Entity Type'),
      '#type' => 'textfield',
      '#default_value' => $config->get('entity_type'),
      '#description' => $this->t('Enter the Janrain Entity Type that users will be saved in.'),
      '#required' => TRUE,
    ];

    $form['flow_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Flow Info'),
      '#open' => TRUE,
    ];

    $form['flow_info']['flow_name'] = [
      '#title' => $this->t('Flow Name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('flow_name'),
      '#description' => $this->t('If blank the value will be <b>@flow_name@</b>', [
        '@flow_name@' => $flow_name,
      ]),
      '#required' => TRUE,
    ];

    $form['flow_info']['flow_version'] = [
      '#title' => $this->t('Flow Version'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the flow version. Currently HEAD is not accepted in Auth API.'),
      '#default_value' => $flow_version,
      '#required' => TRUE,
    ];

    $form['log'] = [
      '#type' => 'details',
      '#title' => $this->t('Log'),
      '#open' => FALSE,
    ];

    $form['log']['enable_janrain_rest_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Janrain Rest log'),
      '#default_value' => $config->get('enable_janrain_rest_log'),
      '#description' => $this->t('Is recommended turn off this value because the Request data is saved on log.'),
    ];

    $form['flow_languages'] = [
      '#type' => 'details',
      '#title' => $this->t('Languages'),
      '#open' => FALSE,
    ];

    foreach ($languages as $language) {

      $flow_language_key = 'flow_language_mapping_';

      $language_name = $language->getName();

      $lid = $language->getId();

      $flow_language_key = $flow_language_key . $lid;

      $form['flow_languages'][$flow_language_key] = [
        '#title' => $language_name . ' (' . $lid . ')',
        '#type' => 'textfield',
        '#default_value' => $config->get($flow_language_key),
        '#description' => $this->t('The Janrain lang code which refers to this language. If blank the value will be <b>@default_language@</b>', [
          '@default_language@' => $default_language,
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('janrain_connect.settings');
    $flow_name = $config->get('flow_name');
    $default_language = $config->get('default_language');
    $flow_version = $form_state->getValue('flow_version');
    $capture_server_url = $form_state->getValue('capture_server_url');
    $client_secret = $form_state->getValue('client_secret');

    if (!empty($form_state->getValue('flow_name'))) {
      $flow_name = $form_state->getValue('flow_name');
    }

    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $language) {

      $value = $default_language;

      $flow_language_key = 'flow_language_mapping_';

      $lid = $language->getId();

      $flow_language_key = $flow_language_key . $lid;

      if (!empty($form_state->getValue($flow_language_key))) {
        $value = $form_state->getValue($flow_language_key);
      }

      $config->set($flow_language_key, $value);
    }

    $config->set('application_id', trim($form_state->getValue('application_id')))
      ->set('flow_name', trim($flow_name))
      ->set('client_id', trim($form_state->getValue('client_id')))
      ->set('client_secret', trim($client_secret))
      ->set('flowjs_url', JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FLOW_JS)
      ->set('config_server', JanrainConnectWebServiceConstants::JANRAIN_CONNECT_CONFIG_SERVER)
      ->set('flow_version', trim($flow_version))
      ->set('app_url', trim($this->getRequest()->getSchemeAndHttpHost()))
      ->set('capture_server_url', trim($capture_server_url))
      ->set('entity_type', trim($form_state->getValue('entity_type')))
      ->set('enable_janrain_rest_log', $form_state->getValue('enable_janrain_rest_log'))
      ->save();
  }

}
