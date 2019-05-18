<?php

namespace Drupal\linkedin_oauth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LinkedinOAuthSettingsForm.
 *
 * @package Drupal\linkedin_oauth\Form
 */
class LinkedinOAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkedin_oauth_settings_form';
  }

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'linkedin_oauth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkedin_oauth.settings');
    $form['api_key'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('LinkedIn API key (Client ID)'),
      '#default_value' => $config->get('api_key'),
    );
    $form['api_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('LinkedIn API secret (Client Secret)'),
      '#default_value' => $config->get('api_secret'),
    );
    $form['redirect_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Redirect internal path, after login'),
      '#default_value' => $config->get('redirect_path'),
      '#description' => t('Leave empty for use site frontpage. Use path with beginning slash (ex.: /node/1)'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate redirect path.
    $path = $form_state->getValue('redirect_path');
    if (!empty($path) && ($path[0] !== '/' || !$this->pathValidator->isValid($path))) {
      $form_state->setErrorByName('redirect_path', $this->t('Use existing path with beginning slash (ex.: /node/1)'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $this->config('linkedin_oauth.settings')
      ->set('api_key', $values['api_key'])
      ->set('api_secret', $values['api_secret'])
      ->set('redirect_path', $values['redirect_path'])
      ->save();
  }

}
