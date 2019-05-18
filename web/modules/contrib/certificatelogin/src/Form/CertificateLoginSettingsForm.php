<?php

namespace Drupal\certificatelogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginManager;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;

/**
 * Main configuration settings form.
 *
 * @package Drupal\certificatelogin\Form
 */
class CertificateLoginSettingsForm extends ConfigFormBase {

  /**
   * External Authentication's map between local users and service users.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  /**
   * External Authentication's service for authenticating users.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauth;

  /**
   * The plugin manager for CA certificate verification.
   *
   * @var \Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginManager
   */
  protected $caCertificateVerificationService;

  /**
   * Constructs a new CertificateLoginSettingsForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Authmap $authmap, ExternalAuth $externalauth, CaSignatureVerificationPluginManager $ca_certificate_verification_service) {
    parent::__construct($config_factory);

    $this->authmap = $authmap;
    $this->externalauth = $externalauth;
    $this->caCertificateVerificationService = $ca_certificate_verification_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('externalauth.authmap'),
      $container->get('externalauth.externalauth'),
      $container->get('plugin.manager.certificatelogin.ca_signature_verification')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'certificatelogin.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'certificate_login_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('certificatelogin.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('If checked, module functionality will be enabled. Ensure that all of the configuration here is set properly before doing so.'),
      '#default_value' => $config->get('enabled') ?: FALSE,
    ];

    $form['login_link_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login link label'),
      '#description' => $this->t('Enter the text for the login link that will appear on the user login form.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('login_link_label') ?: 'Log in with a certificate',
    ];

    $form['login_link_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login link description'),
      '#description' => $this->t('Enter descriptive text for the login link above that will be displayed on hovering over it.'),
      '#rows' => 5,
      '#required' => TRUE,
      '#default_value' => $config->get('login_link_description') ?: 'Instead of logging in with a username and password, use a digital certificate installed in your browser.',
    ];

    $form['client_certificate_server_variable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client certificate server variable'),
      '#description' => $this->t("Enter the server variable name containing the client certificate. Needs to be set up in your Web server configuration, unless you're using Apache.  If so, the value must be <code>SSL_CLIENT_CERT</code>.  See documentation for examples."),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('client_certificate_server_variable') ?: 'CLIENT_CERTIFICATE',
    ];

    $form['ca_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Certification authority (CA) certificate'),
      '#description' => $this->t('Specify a CA certificate in PEM format to use for authenticating client certificates. That is, if a client certificate was not signed by the CA, the user will not be able to register and/or log in. Leave blank to allow any certificates.'),
      '#rows' => 5,
      '#default_value' => $config->get('ca_certificate'),
    ];

    $options = $this->getCaSignatureVerificationPlugins();
    $form['ca_signature_verifier'] = [
      '#type' => 'select',
      '#title' => t('CA signature verification plugin'),
      '#options' => $options,
      '#default_value' => $config->get('ca_signature_verifier') ?: array_keys($options)[0],
      '#description' => t('Please choose the cryptography library plug-in used for verifying CA signatures on client certificates.'),
    ];

    $form['delete_users_on_uninstall'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete users on module uninstallation'),
      '#description' => $this->t('If checked, users registered with this authentication provider will be deleted when the module is uninstalled. Leave empty to keep these users, and allow them to log in with another method.'),
      '#default_value' => $config->get('delete_users_on_uninstall') ?: FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  protected function getCaSignatureVerificationPlugins() {
    $options = [];

    foreach ($this->caCertificateVerificationService->getDefinitions() as $plugin) {
      $options[$plugin['id']] = $plugin['label'];
    }

    return $options;
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
    parent::submitForm($form, $form_state);

    $this->config('certificatelogin.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('login_link_label', $form_state->getValue('login_link_label'))
      ->set('login_link_description', $form_state->getValue('login_link_description'))
      ->set('client_certificate_server_variable', $form_state->getValue('client_certificate_server_variable'))
      ->set('ca_certificate', $form_state->getValue('ca_certificate'))
      ->set('ca_signature_verifier', $form_state->getValue('ca_signature_verifier'))
      ->set('delete_users_on_uninstall', $form_state->getValue('delete_users_on_uninstall'))
      ->save();
  }

}
