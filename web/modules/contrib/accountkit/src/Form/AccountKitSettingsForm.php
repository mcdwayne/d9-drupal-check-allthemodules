<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Account Kit settings.
 */
class AccountKitSettingsForm extends ConfigFormBase {

  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $request_context) {
    $this->setConfigFactory($config_factory);
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
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'accountkit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'accountkit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $accountkit_config = $this->config('accountkit.settings');

    $form['fb_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@facebook-dev">@facebook-dev</a>', array('@facebook-dev' => 'https://developers.facebook.com/apps')),
    );

    $form['fb_settings']['app_id'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $accountkit_config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    );

    $form['fb_settings']['app_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('App Secret'),
      '#default_value' => $accountkit_config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret of your Facebook App here. This value can be found from your App Dashboard.'),
    );

    $form['fb_settings']['api_version'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('API Version'),
      '#default_value' => $accountkit_config->get('api_version'),
      '#description' => $this->t('Copy the API Version of your Facebook App here. This value can be found from your App Dashboard. More information on API versions can be found at <a href="@facebook-changelog">Facebook Platform Changelog</a>.', array('@facebook-changelog' => 'https://developers.facebook.com/docs/apps/changelog')),
    );


    $form['fb_settings']['app_domains'] = array(
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Server Domains - Used for Web SDK'),
      '#description' => $this->t('Copy this value to <em>Server Domains - Used for Web SDK</em> field of your Account Kit App settings.'),
      '#default_value' => $this->requestContext->getHost(),
    );


    $form['module_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Account Kit configurations'),
      '#open' => TRUE,
      '#description' => $this->t('These settings allow you to configure how Account Kit module behaves on your Drupal site'),
    );

    $form['module_settings']['redirect_url'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Redirect URL - Used for Basic Web SDK'),
      '#description' => $this->t('Drupal path where the user should be redirected after successful login. Use <em>&lt;front&gt;</em> to redirect user to your front page.'),
      '#default_value' => $accountkit_config->get('redirect_url'),
    );

    $form['module_settings']['disable_phone_auth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable phone authentication'),
      '#description' => $this->t('Disabling authentication via Email on Account Kit.'),
      '#default_value' => $accountkit_config->get('disable_phone_auth'),
    );

    $form['module_settings']['disable_email_auth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable email authentication'),
      '#description' => $this->t('Disabling authentication via Phone on Account Kit.'),
      '#default_value' => $accountkit_config->get('disable_phone_auth'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^v[1-9]\.[0-9]{1,2}$/', $form_state->getValue('api_version'))) {
      $form_state->setErrorByName('api_version', $this->t('Invalid API version. The syntax for API version is for example <em>v2.8</em>'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('accountkit.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('api_version', $values['api_version'])
      ->set('redirect_url', $values['redirect_url'])
      ->set('disable_phone_auth', $values['disable_phone_auth'])
      ->set('disable_email_auth', $values['disable_email_auth'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
