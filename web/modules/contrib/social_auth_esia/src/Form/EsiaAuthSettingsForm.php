<?php

namespace Drupal\social_auth_esia\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth ESIA.
 */
class EsiaAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Used to check if route exists.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Used to check if path is valid and exists.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProviderInterface $route_provider, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory, $route_provider, $path_validator);
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_esia_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_esia.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_esia.settings');

    $form['esia_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('ESIA settings'),
      '#open' => TRUE,
    ];

    $form['esia_settings']['use_testing_server'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use testing server'),
      '#default_value' => $config->get('use_testing_server'),
      '#description' => $this->t('The testing server will be used. Use only for development purposes.'),
    ];

    $form['esia_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID (mnemonic)'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('The ESIA calls client id in their files as <strong>mnemonics</strong>.'),
    ];

    $form['esia_settings']['scopes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scopes requested with auth'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('The scopes with type of data you want to get access to. Multiple scopes separates by comma. E.g.: fullname, openid, mobile. The "email" scope will be added automatically.'),
      '#attributes' => [
        'placeholder' => [
          'fullname, openid, mobile',
        ],
      ],
    ];

    $form['esia_settings']['signature'] = [
      '#type' => 'details',
      '#title' => $this->t('Signature'),
      '#open' => FALSE,
      '#description' => $this->t('Set paths to files relative to Drupal root: @drupal_root', [
        '@drupal_root' => DRUPAL_ROOT,
      ]),
    ];

    $form['esia_settings']['signature']['certificate_path'] = [
      '#title' => $this->t('Certificate'),
      '#description' => $this->t('Usually this file has <strong>.cer</strong>, <strong>.crt</strong> or <strong>.cert</strong> extension.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('certificate_path'),
      '#attributes' => [
        'placeholder' => '../private/esia-certificate.cer',
      ],
    ];

    $form['esia_settings']['signature']['private_key_path'] = [
      '#title' => $this->t('Private'),
      '#description' => $this->t('Usually this file has <strong>.key</strong>  extension.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('private_key_path'),
      '#attributes' => [
        'placeholder' => '../private/esia-private.key',
      ],
    ];

    $form['esia_settings']['signature']['private_key_pass'] = [
      '#title' => $this->t('Private key password'),
      '#description' => $this->t("If you don't have one, leave it empty."),
      '#type' => 'textfield',
      '#default_value' => $config->get('private_key_pass'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();

    if (!file_exists(DRUPAL_ROOT . $values['certificate_path'])) {
      $form_state->setErrorByName('certificate_path', $this->t('Certificate file does not exist by provided path.'));
    }

    if (!file_exists(DRUPAL_ROOT . $values['private_key_path'])) {
      $form_state->setErrorByName('private_key_path', $this->t('Private key file does not exist by provided path.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_esia.settings')
      ->set('client_id', $values['client_id'])
      ->set('use_testing_server', (bool) $values['use_testing_server'])
      ->set('scopes', $values['scopes'])
      ->set('certificate_path', $values['certificate_path'])
      ->set('private_key_path', $values['private_key_path'])
      ->set('private_key_pass', $values['private_key_pass'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
