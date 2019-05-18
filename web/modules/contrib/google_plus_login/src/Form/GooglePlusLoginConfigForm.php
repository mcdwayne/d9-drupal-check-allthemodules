<?php

namespace Drupal\google_plus_login\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GooglePlusLoginConfigForm extends ConfigFormBase {

  /**
   * @var DrupalKernelInterface
   */
  protected $kernel;

  /**
   * @var RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * GoogleOAuthConfigForm constructor.
   *
   * @param ConfigFactoryInterface $configFactory
   * @param DrupalKernelInterface $kernel
   * @param RequestStack $requestStack
   * @param RouteBuilderInterface $routeBuilder
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    DrupalKernelInterface $kernel,
    RequestStack $requestStack,
    RouteBuilderInterface $routeBuilder
  ){
    parent::__construct($configFactory);
    $this->kernel = $kernel;
    $this->requestStack = $requestStack;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('kernel'),
      $container->get('request_stack'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_plus_login_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'google_plus_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_plus_login.settings');

    if (!$config->get('client_id') || !$config->get('client_secret')) {
      drupal_set_message(
        Link::fromTextAndUrl(
          $this->t('Follow the Google Developer documentation to configure this module.'),
          Url::fromUri('https://developers.google.com/+/web/signin')
        ),
        'warning'
      );
    }

    $form['info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Information'),
    ];

    $form['info']['urls'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        $this->t(
          '<strong>Login URL:</strong> <code>@url</code>',
          ['@url' => $this->getRequest()->getSchemeAndHttpHost() . '/google/login']
        ),
        $this->t(
          '<strong>Authentication URL:</strong> <code>@url</code>',
          ['@url' => $this->getRequest()->getSchemeAndHttpHost() . '/google/authenticate']
        ),
      ],
      '#attributes' => [
        'style' => 'margin-left: 0;',
      ],
    ];

    $form['google'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Developer Settings')
    ];

    $form['google']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['google']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    $form['profile'] = [
      '#type' => 'fieldset',
      '#title' => 'Profile Form Settings',
    ];

    $form['profile']['disable_mail_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable email field'),
      '#default_value' => $config->get('disable_mail_field'),
    ];

    $form['profile']['disable_current_pass_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable current password field'),
      '#default_value' => $config->get('disable_current_pass_field'),
    ];

    $form['profile']['disable_pass_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable password field'),
      '#default_value' => $config->get('disable_pass_field'),
    ];

    $form['profile']['details'] = [
      '#markup' => '<em>' . $this->t('Fields will only be disable if the account is managed by Google Plus Login.') . '</em>',
    ];

    $form['integration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Integration Settings'),
    ];

    $form['integration']['google_login_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override user module login route controller'),
      '#description' => $this->t(implode(' ', [
        'This will replace the controller for <em>/user/login</em>',
        'with Google OAuth login. You can access the Drupal login form from',
        '<em>/user/site-login</em>.',
      ])),
      '#default_value' => $config->get('google_login_override'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_plus_login.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('disable_mail_field', $form_state->getValue('disable_mail_field'))
      ->set('disable_current_pass_field', $form_state->getValue('disable_current_pass_field'))
      ->set('disable_pass_field', $form_state->getValue('disable_pass_field'))
      ->set('google_login_override', $form_state->getValue('google_login_override'))
      ->save();

    $this->kernel->invalidateContainer();
    $this->routeBuilder->rebuild();
  }

}
