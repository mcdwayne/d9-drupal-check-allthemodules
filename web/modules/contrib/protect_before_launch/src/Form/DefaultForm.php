<?php

namespace Drupal\protect_before_launch\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\protect_before_launch\Configuration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultForm.
 *
 * @package Drupal\protect_before_launch\Form
 */
class DefaultForm extends FormBase {

  /**
   * Form ID.
   *
   * @var string
   */
  const FORM_ID = 'protect_before_launch_form';

  /**
   * Protected config.
   *
   * @var \Drupal\protect_before_launch\Configuration
   */
  protected $config;

  /**
   * Constructs a \Drupal\protect_before_launch\Form object.
   *
   * @param \Drupal\protect_before_launch\Configuration $config
   *   The factory for configuration objects.
   */
  public function __construct(Configuration $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('protect_before_launch.configuration')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      Configuration::CONFIG_KEY,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['protect'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#default_value' => $this->config->getProtect(),
      '#required' => TRUE,
      '#options' => [
        Configuration::PROTECT_DISABLED => $this->t('Disabled'),
        Configuration::PROTECT_ENABLED => $this->t('Enabled'),
        Configuration::PROTECT_ENV_ENABLED => $this->t('Auto Enabled by Environment'),
      ],
      '#description' => $this->t('Enable the login for the site.'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->config->getUsername(),
      '#required' => TRUE,
      '#description' => $this->t('The username to use to login.'),
    ];

    $form['password'] = [
      '#type' => 'password_confirm',
      '#required' => FALSE,
      '#description' => $this->t('The password to use for the login'),
    ];

    $form['advanced-section'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#group' => 'advanced',
    ];

    $form['advanced-section']['authentication_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Authentication Type'),
      '#default_value' => $this->config->getAuthenticationType(),
      '#required' => TRUE,
      '#options' => [
        Configuration::AUTH_SIMPLE => $this->t('Standalone Username and password'),
        Configuration::AUTH_DRUPAL => $this->t('Drupal user authentication'),
      ],
      '#description' => $this->t('Select authentication type'),
    ];

    $form['advanced-section']['realm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Realm'),
      '#default_value' => $this->config->getRealm() ?: $this->t('Protected Site'),
      '#required' => TRUE,
      '#description' => $this->t('The realm for the password'),
    ];

    $form['advanced-section']['content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Denied Content'),
      '#default_value' => $this->config->getContent() ?: $this->t('Access Denied'),
      '#required' => TRUE,
      '#description' => $this->t('Text shown when user presses escape.'),
    ];

    $form['advanced-section']['exclude_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude Paths'),
      '#default_value' => $this->config->getExcludePathsText(),
      '#required' => FALSE,
      '#description' => $this->t('Exclude these paths from password protection. Preg match <a href="http://php.net/preg_match" target="_blank">Patterns</a> without delimiter'),
    ];

    $form['advanced-section']['environment_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment Key'),
      '#default_value' => $this->config->getEnvironmentKey() ?: 'AH_NON_PRODUCTION',
      '#required' => TRUE,
      '#description' => $this->t('The Environment key to auto enable'),
    ];

    $form['advanced-section']['environment_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment Value'),
      '#default_value' => $this->config->getEnvironmentValue(),
      '#required' => FALSE,
      '#description' => $this->t('The Environment value to auto enable. leave empty if you want to ignore the value'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config
      ->setProtect($form_state->getValue('protect'))
      ->setUsername($form_state->getValue('username'))
      ->setPassword($form_state->getValue('password'))
      ->setAuthenticationType($form_state->getValue('authentication_type'))
      ->setRealm($form_state->getValue('realm'))
      ->setContent($form_state->getValue('content'))
      ->setExcludePaths($form_state->getValue('exclude_paths'))
      ->setEnvironmentKey($form_state->getValue('environment_key'))
      ->setEnvironmentValue($form_state->getValue('environment_value'));
  }

}
