<?php

namespace Drupal\edstep\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class EdstepSettingsForm extends ConfigFormBase {

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
  public function getFormId() {
    return 'edstep-course-settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['edstep.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edstep.settings');


    // $form['edstep_settings'] = [
    //   '#type' => 'details',
    //   '#title' => t('EdStep settings'),
    //   '#open' => TRUE,
    // ];
    $form['host'] = [
      '#type' => 'select',
      '#title' => t('EdStep site'),
      '#default_value' => $config->get('host') . '|' . $config->get('auth_host'),
      '#options' => [
        'https://edstep.com|https://auth.cerpus-course.com' => t('EdStep.com'),
        'https://edstep-demo1.cerpusdev.net|https://auth-ple.oerdev.net' => t('EdStep demo'),
      ],
      '#required' => TRUE,
    ];
    $form['auth_client_id'] = [
      '#type' => 'textfield',
      '#title' => t('EdStep Client ID'),
      '#default_value' => $config->get('auth_client_id'),
      '#required' => TRUE,
    ];
    $form['auth_client_secret'] = [
      '#type' => 'textfield',
      '#title' => t('EdStep Client Secret'),
      '#default_value' => $config->get('auth_client_secret'),

      '#required' => TRUE,
    ];
    // $form['edstep_advanced'] = [
    //   '#type' => 'details',
    //   '#title' => t('Advanced settings'),
    //   '#open' => FALSE,
    // ];
    // $form['edstep_advanced']['host'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('Override EdStep Course provider'),
    //   '#description' => t("URL to an EdStep provider (https://edstep.com)."),
    //   '#required' => FALSE,
    // ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    list($host, $auth_host) = explode('|', $form_state->getValue('host'));
    $this->config('edstep.settings')
      ->set('host', $host)
      ->set('auth_host', $auth_host)
      ->set('auth_client_id', $form_state->getValue('auth_client_id'))
      ->set('auth_client_secret', $form_state->getValue('auth_client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
