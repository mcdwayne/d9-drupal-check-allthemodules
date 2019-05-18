<?php

namespace Drupal\applenews\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures applenews settings for this site.
 *
 * @internal
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\applenews\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   *   The aggregator processor plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TranslationInterface $string_translation) {
    parent::__construct($config_factory);
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'applenews_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['applenews.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('applenews.settings');
    $curl_opts = $config->get('curl_options');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => t('Apple News Credentials'),
      '#description' => t('You can find your connection information in News Publisher. Go to Channel Info tab to view your API Key and Channel ID.'),
    ];

    $form['credentials']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API Endpoint URL'),
      '#default_value' => $config->get('endpoint'),
      '#description' => t('Publisher API endpoint URL'),
    ];

    $form['credentials']['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key ID'),
      '#default_value' => $config->get('api_key'),
      '#description' => t('Publisher API Key ID'),
    ];

    $form['credentials']['api_secret'] = [
      '#type' => 'password',
      '#title' => t('API Secret Key'),
      '#default_value' => $config->get('api_secret'),
      '#description' => t('Publisher API Secret Key'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['advanced']['ssl'] = [
      '#type' => 'checkbox',
      '#title' => t('SSL verification'),
      '#default_value' => $curl_opts['ssl'],
      '#description' => t('Disabling verification makes the communication insecure.'),
    ];

    $form['advanced']['proxy'] = [
      '#type' => 'textfield',
      '#title' => t('Proxy address'),
      '#default_value' => $curl_opts['proxy'],
      '#description' => t('Proxy server address.'),
    ];

    $form['advanced']['proxy_port'] = [
      '#type' => 'textfield',
      '#title' => t('Proxy port'),
      '#default_value' => $curl_opts['proxy_port'],
      '#size' => 10,
      '#description' => t('Proxy server port number.'),
    ];

    $form['advanced']['api_debug'] = [
      '#type' => 'select',
      '#title' => t('Debug API'),
      '#description' => t('Log all interaction with the Apple News API.'),
      '#options' => ['' => t('Disabled'), 1 => t('Enabled')],
      '#default_value' => FALSE,
    ];

    // Show delete button only when all the fields are prepopulated.
    if (!empty($endpoint) && !empty($api_key) && !empty($api_secret)) {
      $form['delete_config'] = [
        '#markup' => $this->t('<a href="!url">Delete configuration</a>', ['!url' => Url::fromUri('/admin/config/content/applenews/settings/delete')]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('applenews.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('curl_options.ssl', $form_state->getValue('ssl'))
      ->set('curl_options.proxy', $form_state->getValue('proxy'))
      ->set('curl_options.proxy_port', $form_state->getValue('proxy_port'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
