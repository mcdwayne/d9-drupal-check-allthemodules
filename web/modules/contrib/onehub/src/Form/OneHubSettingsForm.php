<?php

namespace Drupal\onehub\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OneHubSettingsForm.
 */
class OneHubSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a new OneHubSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $config_factory->get('onehub.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'onehub.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onehub_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config;

    // Descriptive text.
    $tdesc = t('You can obtain the application info from your <a href="@app" target="_blank">OneHub Developer Page</a>.', [
      '@app' => 'https://ws.onehub.com/oauth2_clients',
    ]);
    $form['title'] = [
      '#type' => 'item',
      '#markup' => '<h4><strong>' . $tdesc . '</strong></h4>',
    ];

    // Set up redirect uri.
    global $base_url;
    $base = $base_url;

    // Make sure we are on https.
    if (strpos($base, 'http:') !== FALSE) {
      $base = str_replace('http:', 'https:', $base);
    }
    $redirect_uri = $base . ONEHUB_REDIRECT_URI;

    $form['redirect'] = [
      '#type' => 'fieldset',
      '#title' => t('Redirect URI'),
    ];

    // Descriptive text.
    $rdesc = t('Set your application Redirect URI to @redirect', [
      '@redirect' => $redirect_uri,
    ]);

    // Redirect URI text
    $form['redirect']['onehub_redirect_uri'] = [
      '#type' => 'item',
      '#markup' => '<span><strong>' . $rdesc . '</strong></span>',
    ];

    // Client Secret.
    $form['onehub_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OneHub Client Secret'),
      '#default_value' => $config->get('onehub_client_secret'),
      '#required' => TRUE,
    ];

    // Application ID.
    $form['onehub_application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OneHub Application ID'),
      '#default_value' => $config->get('onehub_application_id'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('onehub.settings');

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'onehub_') !== FALSE) {
        $config->set($key, $value);
      }
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
